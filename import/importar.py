#!/usr/bin/env python3
"""
Script de importación: Validación_Tags.xlsx → MySQL arryanaes
Ejecutar UNA SOLA VEZ después de crear la BD con schema.sql

Uso:
    pip install pandas openpyxl mysql-connector-python
    python3 importar.py --host localhost --user root --password TU_PASSWORD --file Validación_Tags.xlsx
"""

import argparse
import sys
import re
import pandas as pd

try:
    import mysql.connector
except ImportError:
    print("Instala: pip install mysql-connector-python")
    sys.exit(1)

def clean_str(val):
    if pd.isna(val):
        return ''
    return str(val).strip()

def clean_int(val):
    try:
        return str(int(float(val)))
    except:
        return ''

def main():
    parser = argparse.ArgumentParser(description='Importar datos de fraccionamiento')
    parser.add_argument('--host',     default='localhost')
    parser.add_argument('--user',     default='root')
    parser.add_argument('--password', default='')
    parser.add_argument('--db',       default='arryanaes')
    parser.add_argument('--file',     default='Validación_Tags.xlsx')
    args = parser.parse_args()

    print(f"Conectando a {args.host}/{args.db}...")
    conn = mysql.connector.connect(
        host=args.host, user=args.user, password=args.password, database=args.db
    )
    cur = conn.cursor()

    print(f"Leyendo {args.file}...")
    df = pd.read_excel(args.file, sheet_name='Employees')
    df['Ext_clean'] = df['Ext'].apply(lambda x: clean_int(x) if pd.notna(x) and str(x) not in ['#VALUE!', 'nan'] else '')
    df['Calle_clean'] = df['Calle'].apply(lambda x: clean_str(x) if str(x) != '#VALUE!' else '')

    # Group by UserID for unique residents
    residentes_df = df.groupby('UserID').first().reset_index()

    print(f"Importando {len(residentes_df)} residentes...")
    residente_map = {}  # UserID -> DB id

    for _, row in residentes_df.iterrows():
        uid   = int(float(row['UserID'])) if pd.notna(row['UserID']) else 0
        nom   = clean_str(row['First name'])
        ape   = clean_str(row['Last name'])
        seg   = clean_str(row['Middle name'])
        ident = clean_str(row['Identification'])
        calle = df.loc[df['UserID'] == row['UserID'], 'Calle_clean'].values[0]
        ext   = df.loc[df['UserID'] == row['UserID'], 'Ext_clean'].values[0]
        dept  = clean_str(row['Department'])
        com   = clean_str(row.get('Comentario', ''))

        cur.execute("""
            INSERT INTO residentes (user_id_externo, nombre, apellidos, segundo_nombre, calle, numero_ext, identificacion, departamento, comentario)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)
        """, (uid, nom, ape, seg, calle, ext, ident, dept, com))
        residente_map[uid] = cur.lastrowid

    conn.commit()
    print(f"  ✅ {len(residente_map)} residentes importados")

    print(f"Importando tags...")
    tags_ok = 0
    tags_skip = 0

    for _, row in df.iterrows():
        card = str(row['Card Number'])
        if pd.isna(row['Card Number']) or card in ['nan', ''] or not card.replace('.0','').replace('.','').isdigit():
            tags_skip += 1
            continue

        card_num = str(int(float(row['Card Number'])))
        uid = int(float(row['UserID'])) if pd.notna(row['UserID']) else 0
        res_id = residente_map.get(uid)
        if not res_id:
            tags_skip += 1
            continue

        facility   = clean_int(row.get('Facility code', ''))
        ag         = clean_str(row.get('Access group', ''))
        pago       = clean_str(row.get('PAGO VALIDO', '')).upper()
        estatus    = 'ACTIVO' if pago == 'SI' else 'MOROSO'

        fecha_desde = None
        fecha_hasta = None
        try:
            if pd.notna(row['From (Valid date)']):
                fecha_desde = pd.to_datetime(str(row['From (Valid date)'])[:10], dayfirst=True).strftime('%Y-%m-%d')
        except: pass
        try:
            if pd.notna(row['Until (Valid date)']):
                fecha_hasta = pd.to_datetime(str(row['Until (Valid date)'])[:10], dayfirst=True).strftime('%Y-%m-%d')
        except: pass

        try:
            cur.execute("""
                INSERT IGNORE INTO tags (residente_id, numero_tag, facility_code, fecha_desde, fecha_hasta, access_group, estatus)
                VALUES (%s,%s,%s,%s,%s,%s,%s)
            """, (res_id, card_num, facility, fecha_desde, fecha_hasta, ag, estatus))
            tags_ok += 1
        except Exception as e:
            print(f"  ⚠️  Tag {card_num}: {e}")
            tags_skip += 1

    conn.commit()
    print(f"  ✅ {tags_ok} tags importados, {tags_skip} omitidos")

    cur.close()
    conn.close()
    print("\n🎉 Importación completa!")
    print("   Usuarios del sistema:")
    print("   admin / password (cambiar en producción)")
    print("   consulta / password (cambiar en producción)")

if __name__ == '__main__':
    main()
