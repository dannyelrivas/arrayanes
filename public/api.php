<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Rutas públicas
if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode(login($data['username'] ?? '', $data['password'] ?? ''));
    exit;
}

if ($action === 'logout') {
    logout();
    echo json_encode(['success' => true]);
    exit;
}

// Rutas protegidas
requireLogin();
$db = getDB();

switch ($action) {

    // ─── RESIDENTES ───────────────────────────────────────────────
    case 'listar_residentes':
        $calle    = $_GET['calle'] ?? '';
        $nombre   = $_GET['nombre'] ?? '';
        $tag      = $_GET['tag'] ?? '';
        $estatus  = $_GET['estatus'] ?? '';
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $limit    = 25;
        $offset   = ($page - 1) * $limit;

        $where = ["r.activo = 1"];
        $params = [];

        if ($calle) { $where[] = "r.calle = ?"; $params[] = $calle; }
        if ($nombre) { $where[] = "CONCAT(r.nombre, ' ', COALESCE(r.apellidos,'')) LIKE ?"; $params[] = "%$nombre%"; }
        if ($tag) {
            $where[] = "EXISTS (SELECT 1 FROM tags t2 WHERE t2.residente_id = r.id AND t2.numero_tag LIKE ? AND t2.activo=1)";
            $params[] = "%$tag%";
        }
        if ($estatus === 'MOROSO') {
            $where[] = "EXISTS (SELECT 1 FROM tags t3 WHERE t3.residente_id = r.id AND t3.estatus='MOROSO' AND t3.activo=1)";
        } elseif ($estatus === 'ACTIVO') {
            $where[] = "EXISTS (SELECT 1 FROM tags t3 WHERE t3.residente_id = r.id AND t3.estatus='ACTIVO' AND t3.activo=1)
                        AND NOT EXISTS (SELECT 1 FROM tags t3 WHERE t3.residente_id = r.id AND t3.estatus='MOROSO' AND t3.activo=1)";
        } elseif ($estatus === 'INACTIVO') {
            $where[] = "NOT EXISTS (SELECT 1 FROM tags t3 WHERE t3.residente_id = r.id AND t3.activo=1)
                        OR EXISTS (SELECT 1 FROM tags t3 WHERE t3.residente_id = r.id AND t3.estatus='INACTIVO' AND t3.activo=1)";
        }

        $whereStr = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM residentes r WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM pagos p 
                 WHERE p.residente_id = r.id AND p.pagado = 1
                   AND (p.anio > YEAR(CURDATE()) OR (p.anio = YEAR(CURDATE()) AND p.mes > MONTH(CURDATE())))
                ) AS meses_adelantados,
                   r.id, r.nombre, r.apellidos, r.calle, r.numero_ext, r.numero_int,
                   r.identificacion, r.comentario,
                    COUNT(t.id) AS total_tags,
                    SUM(CASE WHEN t.estatus = 'ACTIVO'   AND t.activo = 1 THEN 1 ELSE 0 END) AS tags_activos,
                    SUM(CASE WHEN t.estatus = 'MOROSO'   AND t.activo = 1 THEN 1 ELSE 0 END) AS tags_morosos,
                    SUM(CASE WHEN t.estatus = 'INACTIVO' AND t.activo = 1 THEN 1 ELSE 0 END) AS tags_inactivos,
                   (SELECT pagado FROM pagos p WHERE p.residente_id=r.id AND p.anio=YEAR(CURDATE()) AND p.mes=MONTH(CURDATE()) LIMIT 1) AS pago_mes_actual,
                   (SELECT pagado FROM pagos p WHERE p.residente_id=r.id AND p.anio=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND p.mes=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) LIMIT 1) AS pago_mes_anterior
            FROM residentes r
            LEFT JOIN tags t ON t.residente_id = r.id AND t.activo = 1
            WHERE $whereStr
            GROUP BY r.id
            ORDER BY r.calle, r.numero_ext, r.nombre
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
        break;

    case 'detalle_residente':
        $id = (int)($_GET['id'] ?? 0);
        $r = $db->prepare("SELECT * FROM residentes WHERE id = ? AND activo = 1");
        $r->execute([$id]);
        $residente = $r->fetch();
        if (!$residente) { echo json_encode(['error' => 'No encontrado']); break; }

        $tags = $db->prepare("SELECT * FROM tags WHERE residente_id = ? AND activo = 1 ORDER BY numero_tag");
        $tags->execute([$id]);

        // $pagos = $db->prepare("SELECT * FROM pagos WHERE residente_id = ? ORDER BY anio DESC, mes DESC LIMIT 24");
        $pagos = $db->prepare("SELECT * FROM pagos WHERE residente_id = ? ORDER BY anio ASC, mes ASC");

        $stAdel = $db->prepare("
            SELECT COUNT(*) FROM pagos
            WHERE residente_id = ? AND pagado = 1
              AND (anio > YEAR(CURDATE()) OR (anio = YEAR(CURDATE()) AND mes > MONTH(CURDATE())))
        ");
        $stAdel->execute([$id]);
        $meses_adelantados = (int)$stAdel->fetchColumn();

        $pagos->execute([$id]);

        echo json_encode(['success'=>true,'meses_adelantados' => $meses_adelantados, 'residente' => $residente, 'tags' => $tags->fetchAll(), 'pagos' => $pagos->fetchAll()]);
        break;

    case 'calles':
        $stmt = $db->query("SELECT DISTINCT calle FROM residentes WHERE activo=1 AND calle != '' ORDER BY calle");
        echo json_encode(['success' => true, 'calles' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
        break;

    // ─── PAGOS ────────────────────────────────────────────────────
    case 'registrar_pago':
        requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $residente_id = (int)($data['residente_id'] ?? 0);
        $anio   = (int)($data['anio'] ?? date('Y'));
        $mes    = (int)($data['mes'] ?? date('n'));
        $pagado = (int)($data['pagado'] ?? 1);
        $monto  = (float)($data['monto'] ?? 0);
        $metodo = $data['metodo'] ?? 'EFECTIVO';
        $ref    = $data['referencia'] ?? '';
        $notas  = $data['notas'] ?? '';
        $user   = currentUser();

        // Get current status before payment
        $stmtStatus = $db->prepare("SELECT estatus FROM tags WHERE residente_id = ? AND activo = 1 LIMIT 1");
        $stmtStatus->execute([$residente_id]);
        $estatus_previo = $stmtStatus->fetchColumn() ?: 'MOROSO';

        // Upsert pago
        $stmt = $db->prepare("
            INSERT INTO pagos (residente_id, anio, mes, pagado, fecha_pago, monto, metodo_pago, referencia, estatus_previo, registrado_por, notas)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE pagado=VALUES(pagado), fecha_pago=NOW(), monto=VALUES(monto),
                metodo_pago=VALUES(metodo_pago), referencia=VALUES(referencia), 
                registrado_por=VALUES(registrado_por), notas=VALUES(notas)
        ");
        $stmt->execute([$residente_id, $anio, $mes, $pagado, $monto, $metodo, $ref, $estatus_previo, $user['id'], $notas]);

        // Update tag status
        if ($pagado) {
            $nuevoEstatus = 'ACTIVO';
            // If was moroso last month and paid this month -> ACTIVO (reactivado)
            $stmtTags = $db->prepare("SELECT id, estatus FROM tags WHERE residente_id = ? AND activo = 1");
            $stmtTags->execute([$residente_id]);
            $tagsData = $stmtTags->fetchAll();
            foreach ($tagsData as $tag) {
                if ($tag['estatus'] !== 'ACTIVO') {
                    // Log history
                    $db->prepare("INSERT INTO historial_estatus (tag_id, residente_id, estatus_anterior, estatus_nuevo, motivo, usuario_id) VALUES (?,?,?,?,?,?)")
                       ->execute([$tag['id'], $residente_id, $tag['estatus'], 'ACTIVO', 'Pago registrado', $user['id']]);
                }
            }
            $db->prepare("UPDATE tags SET estatus='ACTIVO', updated_at=NOW() WHERE residente_id=? AND activo=1")->execute([$residente_id]);
        }

        echo json_encode(['success' => true, 'message' => 'Pago registrado correctamente']);
        break;

    case 'marcar_moroso':
        requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $residente_id = (int)($data['residente_id'] ?? 0);
        $user = currentUser();

        $stmtTags = $db->prepare("SELECT id, estatus FROM tags WHERE residente_id = ? AND activo = 1");
        $stmtTags->execute([$residente_id]);
        foreach ($stmtTags->fetchAll() as $tag) {
            if ($tag['estatus'] !== 'MOROSO') {
                $db->prepare("INSERT INTO historial_estatus (tag_id, residente_id, estatus_anterior, estatus_nuevo, motivo, usuario_id) VALUES (?,?,?,?,?,?)")
                   ->execute([$tag['id'], $residente_id, $tag['estatus'], 'MOROSO', 'Marcado manualmente', $user['id']]);
            }
        }
        $db->prepare("UPDATE tags SET estatus='MOROSO', updated_at=NOW() WHERE residente_id=? AND activo=1")->execute([$residente_id]);
        echo json_encode(['success' => true]);
        break;

    case 'marcar_inactivo':
        requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $residente_id = (int)($data['residente_id'] ?? 0);
        $user = currentUser();

        $stmtTags = $db->prepare("SELECT id, estatus FROM tags WHERE residente_id = ? AND activo = 1");
        $stmtTags->execute([$residente_id]);
        foreach ($stmtTags->fetchAll() as $tag) {
            if ($tag['estatus'] !== 'INACTIVO') {
                $db->prepare("INSERT INTO historial_estatus (tag_id, residente_id, estatus_anterior, estatus_nuevo, motivo, usuario_id) VALUES (?,?,?,?,?,?)")
                   ->execute([$tag['id'], $residente_id, $tag['estatus'], 'INACTIVO', 'Marcado como inactivo', $user['id']]);
            }
        }
        $db->prepare("UPDATE tags SET estatus='INACTIVO', updated_at=NOW() WHERE residente_id=? AND activo=1")->execute([$residente_id]);
        echo json_encode(['success' => true]);
        break;

    case 'proceso_morosos':
        requireAdmin();
        // Mark all residents who didn't pay last month as morosos
        $user = currentUser();
        $anio_ant = date('Y', strtotime('-1 month'));
        $mes_ant  = date('n', strtotime('-1 month'));

        // Find residents without payment last month OR with pagado=0
        $stmt = $db->prepare("
            SELECT DISTINCT r.id FROM residentes r
            WHERE r.activo = 1
              AND (
                NOT EXISTS (SELECT 1 FROM pagos p WHERE p.residente_id=r.id AND p.anio=? AND p.mes=?)
                OR EXISTS (SELECT 1 FROM pagos p WHERE p.residente_id=r.id AND p.anio=? AND p.mes=? AND p.pagado=0)
              )
        ");
        $stmt->execute([$anio_ant, $mes_ant, $anio_ant, $mes_ant]);
        $morosos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $count = 0;
        foreach ($morosos as $rid) {
            $stmtTags = $db->prepare("SELECT id, estatus FROM tags WHERE residente_id=? AND activo=1");
            $stmtTags->execute([$rid]);
            foreach ($stmtTags->fetchAll() as $tag) {
                if ($tag['estatus'] !== 'MOROSO') {
                    $db->prepare("INSERT INTO historial_estatus (tag_id, residente_id, estatus_anterior, estatus_nuevo, motivo, usuario_id) VALUES (?,?,?,?,?,?)")
                       ->execute([$tag['id'], $rid, $tag['estatus'], 'MOROSO', 'Proceso automático de morosos', $user['id']]);
                }
            }
            $db->prepare("UPDATE tags SET estatus='MOROSO' WHERE residente_id=? AND activo=1")->execute([$rid]);
            $count++;
        }
        echo json_encode(['success' => true, 'marcados' => $count]);
        break;

    case 'stats_dashboard':
        $anio = (int)date('Y');
        $mes  = (int)date('n');

        $total   = (int)$db->query("SELECT COUNT(*) FROM residentes WHERE activo=1")->fetchColumn();
        $morosos = (int)$db->query("SELECT COUNT(DISTINCT residente_id) FROM tags WHERE estatus='MOROSO' AND activo=1")->fetchColumn();
        $inactivos = (int)$db->query("
            SELECT COUNT(*) FROM residentes r
            WHERE r.activo = 1
              AND (
                NOT EXISTS (SELECT 1 FROM tags t WHERE t.residente_id = r.id AND t.activo = 1)
                OR NOT EXISTS (SELECT 1 FROM tags t WHERE t.residente_id = r.id AND t.estatus != 'INACTIVO' AND t.activo = 1)
              )
        ")->fetchColumn();

        $stPag = $db->prepare("SELECT COUNT(*) FROM pagos WHERE anio=? AND mes=? AND pagado=1");
        $stPag->execute([$anio, $mes]);
        $pagaron = (int)$stPag->fetchColumn();

        $total_tags   = (int)$db->query("SELECT COUNT(*) FROM tags WHERE activo=1")->fetchColumn();
        $tags_morosos = (int)$db->query("SELECT COUNT(*) FROM tags WHERE estatus='MOROSO' AND activo=1")->fetchColumn();

        $stMeses = $db->query("
            SELECT anio, mes, COUNT(*) as pagados
            FROM pagos WHERE pagado=1
            GROUP BY anio, mes
            ORDER BY anio DESC, mes DESC
            LIMIT 6
        ");

        // Residentes con pagos adelantados
        $stAdel = $db->prepare("
            SELECT COUNT(DISTINCT residente_id) as total
            FROM pagos
            WHERE pagado = 1
              AND (anio > ? OR (anio = ? AND mes > ?))
        ");
        $stAdel->execute([$anio, $anio, $mes]);
        $adelantados = (int)$stAdel->fetchColumn();

        echo json_encode([
            'success'      => true,
            'total'        => $total,
            'morosos'      => $morosos,
            'activos'      => $total - $morosos - $inactivos,
            'pagaron_mes'  => $pagaron,
            'total_tags'   => $total_tags,
            'tags_morosos' => $tags_morosos,
            'pagos_meses'  => $stMeses->fetchAll(),
            'mes_actual'   => $mes,
            'anio_actual'  => $anio,
            'adelantados' => $adelantados,
            'inactivos' => $inactivos,
        ]);
    break;

    case 'guardar_residente':
        requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        
        if ($id) {
            $stmt = $db->prepare("UPDATE residentes SET nombre=?, apellidos=?, calle=?, numero_ext=?, numero_int=?, comentario=? WHERE id=?");
            $stmt->execute([$data['nombre'], $data['apellidos'], $data['calle'], $data['numero_ext'], $data['numero_int'], $data['comentario'], $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO residentes (nombre, apellidos, calle, numero_ext, numero_int, comentario) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$data['nombre'], $data['apellidos'], $data['calle'], $data['numero_ext'], $data['numero_int'], $data['comentario']]);
            $id = $db->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'historial_pagos':
        $residente_id = (int)($_GET['residente_id'] ?? 0);
        $stmt = $db->prepare("
            SELECT p.*, us.nombre as registrado_nombre
            FROM pagos p
            LEFT JOIN usuarios_sistema us ON us.id = p.registrado_por
            WHERE p.residente_id = ?
            ORDER BY p.anio DESC, p.mes DESC
        ");
        $stmt->execute([$residente_id]);
        echo json_encode(['success' => true, 'pagos' => $stmt->fetchAll()]);
        break;

    default:
        echo json_encode(['error' => 'Acción no reconocida: ' . htmlspecialchars($action)]);
}
