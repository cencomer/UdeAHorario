#!/usr/bin/env python3
"""
parse_csv.py - Parses Docentes.csv, Horario.csv, and Sesiones.csv
and generates a normalized data.json with multi-cohort structure.
"""

import csv
import json
import re
import os
from pathlib import Path
from unicodedata import normalize

# ─── Paths ───────────────────────────────────────────────────────────────────
BASE_DIR = Path(__file__).resolve().parent.parent
DATA_DIR = BASE_DIR / "data"
OUTPUT_PATH = DATA_DIR / "data.json"

# ─── Constants ────────────────────────────────────────────────────────────────
COLORS = ["#34C759", "#FF9500", "#AF52DE", "#FF3B30", "#007AFF", "#5AC8FA", "#FF2D55", "#FFCC00"]

YEAR = 2026

MONTH_MAP = {
    "enero": 1, "febrero": 2, "marzo": 3, "abril": 4,
    "mayo": 5, "junio": 6, "julio": 7, "agosto": 8,
    "septiembre": 9, "octubre": 10, "noviembre": 11, "diciembre": 12,
    # Abbreviations found in date entries
    "ene": 1, "feb": 2, "mar": 3, "abr": 4,
    "may": 5, "jun": 6, "jul": 7, "ago": 8,
    "sept": 9, "sep": 9, "oct": 10, "nov": 11, "dic": 12,
}

DAY_ABBREV_MAP = {
    "D": "Domingo", "L": "Lunes", "M": "Martes", "W": "Miércoles",
    "J": "Jueves", "V": "Viernes", "S": "Sábado",
}

# ─── Helpers ──────────────────────────────────────────────────────────────────

def slugify(text: str) -> str:
    """Convert text to a URL-friendly slug."""
    text = normalize("NFKD", text).encode("ascii", "ignore").decode("ascii")
    text = re.sub(r"[^\w\s-]", "", text.lower())
    text = re.sub(r"[-\s]+", "-", text).strip("-")
    return text


def read_csv(filename: str) -> list[dict]:
    """Read a CSV file with UTF-8-BOM support."""
    filepath = DATA_DIR / filename
    with open(filepath, "r", encoding="utf-8-sig", newline="") as f:
        reader = csv.DictReader(f)
        rows = []
        for row in reader:
            rows.append(row)
    return rows


def get_icon(nombre_materia: str) -> str:
    """Determine icon based on subject name keywords."""
    nombre = nombre_materia.lower()
    if "cartografía" in nombre or "cartografia" in nombre:
        return "map"
    if "tierra" in nombre or "ecosistema" in nombre:
        return "trees"
    if "rural" in nombre:
        return "home"
    if "buen vivir" in nombre:
        return "sun"
    if "pedagogía" in nombre or "pedagógica" in nombre or "pedagogia" in nombre or "pedagogica" in nombre or "práctica" in nombre or "practica" in nombre:
        return "bookOpen"
    if "español" in nombre or "espanol" in nombre or "lengua" in nombre or "literatura" in nombre:
        return "penTool"
    return "bookOpen"


def parse_horario_string(horario_str: str) -> list[dict]:
    """
    Parse schedule strings like 'V9-17', 'S8-16', 'V14-19 - S7-12 - D14-19'.
    Returns list of {dia, horaInicio, horaFin}.
    """
    horarios = []
    if not horario_str or not horario_str.strip():
        return horarios

    parts = [p.strip() for p in horario_str.split(" - ")]
    for part in parts:
        if not part:
            continue
        # Handle DD_ prefix (e.g., DD_7-12 means Domingo)
        match = re.match(r"^(DD_|[DLMWJVS])(\d{1,2})-(\d{1,2})$", part)
        if match:
            day_code = match.group(1)
            hora_inicio = int(match.group(2))
            hora_fin = int(match.group(3))
            if day_code == "DD_":
                dia = "Domingo"
            else:
                dia = DAY_ABBREV_MAP.get(day_code, day_code)
            horarios.append({
                "dia": dia,
                "horaInicio": hora_inicio,
                "horaFin": hora_fin,
            })
    return horarios


def parse_modalidad(raw: str) -> str:
    """Normalize modalidad values."""
    raw = raw.strip()
    if not raw or raw == "0":
        return "Pre"
    # ATM is a typo/variant for AMT
    if raw == "ATM":
        return "AMT"
    # Known valid values
    if raw in ("Pre", "AMT", "Prác", "Lab"):
        return raw
    return "Pre"


def parse_sesiones_fechas(resumen_fechas: str) -> list[dict]:
    """
    Parse the 'Resumen de fechas' field from Sesiones.csv.
    Returns list of {fecha: 'YYYY-MM-DD', modalidad: str}.
    """
    sesiones = []
    if not resumen_fechas or not resumen_fechas.strip():
        return sesiones

    # Find all month sections: 📅MonthName: ...
    month_sections = re.split(r"📅\s*", resumen_fechas)

    for section in month_sections:
        if not section.strip():
            continue

        # Extract month name from the section header
        header_match = re.match(r"(\w+)\s*:", section)
        if not header_match:
            continue

        month_name = header_match.group(1).lower()
        month_num = MONTH_MAP.get(month_name)
        if month_num is None:
            continue

        # Find all date entries: dayname DD monthabbrev ▶️ modalidad 🔸
        # Pattern: (lun|mar|mié|jue|vie|sáb|dom) DD (monthabbrev) ▶️ (modalidad) 🔸
        entries = re.findall(
            r"(?:lun|mar|mié|jue|vie|sáb|dom)\s+(\d{1,2})\s+(\w+)\s*▶️\s*(.*?)\s*🔸",
            section
        )

        for day_str, month_abbrev, modalidad_raw in entries:
            day = int(day_str)
            # Use month from the entry's abbreviation if available
            entry_month = MONTH_MAP.get(month_abbrev.lower(), month_num)
            modalidad = parse_modalidad(modalidad_raw)

            fecha = f"{YEAR}-{entry_month:02d}-{day:02d}"
            sesiones.append({
                "fecha": fecha,
                "modalidad": modalidad,
            })

    return sesiones


def build_cohort_key(campus: str, programa: str, cohorte: str) -> str:
    """Build a unique cohort identifier."""
    return f"{slugify(campus)}-{slugify(programa)}-{cohorte.strip()}"


# ─── Main Processing ─────────────────────────────────────────────────────────

def main():
    # Read all CSVs
    docentes_rows = read_csv("Docentes.csv")
    horario_rows = read_csv("Horario.csv")
    sesiones_rows = read_csv("Sesiones.csv")

    # Collect unique campus and programs
    campus_set = {}
    programas_set = {}
    cohortes_data = {}  # key -> cohort data

    # ─── Process Docentes ─────────────────────────────────────────────────
    # Build a lookup by Código-Gr for docentes
    docente_by_codigo = {}  # Código-Gr -> nombre del profesor

    for row in docentes_rows:
        campus_name = row.get("Campus | Prog.", "").strip()
        programa_name = row.get("Programa-Relacionado", "").strip()
        cohorte_val = row.get("Cohorte", "").strip()
        codigo_gr = row.get("Código-Gr", "").strip()
        nombre_materia = row.get("Nombre Materia", "").strip()
        nombre_profesor = row.get("Nombre del profesor", "").strip()

        if not campus_name or not programa_name:
            continue

        campus_id = slugify(campus_name)
        programa_id = slugify(programa_name)

        campus_set[campus_id] = campus_name
        programas_set[programa_id] = programa_name

        cohort_key = build_cohort_key(campus_name, programa_name, cohorte_val)

        if cohort_key not in cohortes_data:
            cohortes_data[cohort_key] = {
                "id": cohort_key,
                "campus_id": campus_id,
                "programa_id": programa_id,
                "cohorte": cohorte_val,
                "docentes": [],
                "materias": [],
                "sesiones": [],
                "_docente_set": set(),
                "_materia_map": {},
            }

        # Add docente if not already added
        cohort = cohortes_data[cohort_key]
        if nombre_profesor and nombre_profesor not in cohort["_docente_set"]:
            cohort["_docente_set"].add(nombre_profesor)
            doc_id = f"DOC{len(cohort['docentes']) + 1:03d}"
            cohort["docentes"].append({
                "id": doc_id,
                "nombre": nombre_profesor,
            })

        # Store docente mapping for this codigo
        docente_by_codigo[codigo_gr] = nombre_profesor

    # ─── Process Horario ──────────────────────────────────────────────────
    for row in horario_rows:
        campus_name = row.get("Campus | Prog.", "").strip()
        programa_name = row.get("Programa-Relacionado", "").strip()
        cohorte_val = row.get("Cohorte", "").strip()
        codigo_gr = row.get("Código-Gr", "").strip()
        nombre_materia = row.get("Nombre Materia", "").strip()
        resumen_horarios = row.get("Resumen horarios", "").strip()
        cupo = row.get("Cupo", "").strip()

        if not campus_name or not programa_name:
            continue

        campus_id = slugify(campus_name)
        programa_id = slugify(programa_name)

        campus_set[campus_id] = campus_name
        programas_set[programa_id] = programa_name

        cohort_key = build_cohort_key(campus_name, programa_name, cohorte_val)

        if cohort_key not in cohortes_data:
            cohortes_data[cohort_key] = {
                "id": cohort_key,
                "campus_id": campus_id,
                "programa_id": programa_id,
                "cohorte": cohorte_val,
                "docentes": [],
                "materias": [],
                "sesiones": [],
                "_docente_set": set(),
                "_materia_map": {},
            }

        cohort = cohortes_data[cohort_key]

        # Find docente_id for this materia
        docente_nombre = docente_by_codigo.get(codigo_gr, "")
        docente_id = ""
        for doc in cohort["docentes"]:
            if doc["nombre"] == docente_nombre:
                docente_id = doc["id"]
                break

        # If docente not found in this cohort but exists, add them
        if not docente_id and docente_nombre and docente_nombre not in cohort["_docente_set"]:
            cohort["_docente_set"].add(docente_nombre)
            doc_id = f"DOC{len(cohort['docentes']) + 1:03d}"
            cohort["docentes"].append({
                "id": doc_id,
                "nombre": docente_nombre,
            })
            docente_id = doc_id
        elif not docente_id and docente_nombre:
            for doc in cohort["docentes"]:
                if doc["nombre"] == docente_nombre:
                    docente_id = doc["id"]
                    break

        # Parse horarios
        horarios = parse_horario_string(resumen_horarios)

        # Color assignment based on order within cohort
        color_idx = len(cohort["_materia_map"]) % len(COLORS)

        # Extract the base code (without group suffix)
        codigo_base = codigo_gr.split("-")[0] if "-" in codigo_gr else codigo_gr

        materia_entry = {
            "id": codigo_gr,
            "codigo": codigo_base,
            "nombre": nombre_materia,
            "docente_id": docente_id,
            "horarios": horarios,
            "resumen_horarios": resumen_horarios,
            "icon": get_icon(nombre_materia),
            "color": COLORS[color_idx],
        }

        if cupo:
            materia_entry["cupo"] = int(cupo) if cupo.isdigit() else cupo

        cohort["_materia_map"][codigo_gr] = materia_entry
        cohort["materias"].append(materia_entry)

    # ─── Process Sesiones ─────────────────────────────────────────────────
    for row in sesiones_rows:
        campus_name = row.get("Campus | Prog.", "").strip()
        programa_name = row.get("Programa-Relacionado", "").strip()
        cohorte_val = row.get("Cohorte", "").strip()
        codigo_gr = row.get("Código-Gr", "").strip()
        nombre_materia = row.get("Nombre Materia", "").strip()
        resumen_fechas = row.get("Resumen de fechas", "").strip()

        if not campus_name or not programa_name:
            continue

        campus_id = slugify(campus_name)
        programa_id = slugify(programa_name)

        campus_set[campus_id] = campus_name
        programas_set[programa_id] = programa_name

        cohort_key = build_cohort_key(campus_name, programa_name, cohorte_val)

        if cohort_key not in cohortes_data:
            cohortes_data[cohort_key] = {
                "id": cohort_key,
                "campus_id": campus_id,
                "programa_id": programa_id,
                "cohorte": cohorte_val,
                "docentes": [],
                "materias": [],
                "sesiones": [],
                "_docente_set": set(),
                "_materia_map": {},
            }

        cohort = cohortes_data[cohort_key]

        # Parse sessions from Resumen de fechas
        parsed_sesiones = parse_sesiones_fechas(resumen_fechas)

        for sesion in parsed_sesiones:
            cohort["sesiones"].append({
                "materia_id": codigo_gr,
                "fecha": sesion["fecha"],
                "modalidad": sesion["modalidad"],
            })

    # ─── Build Output ─────────────────────────────────────────────────────
    # Sort campus and programas
    campus_list = sorted(
        [{"id": cid, "nombre": cname} for cid, cname in campus_set.items()],
        key=lambda x: x["nombre"]
    )
    programas_list = sorted(
        [{"id": pid, "nombre": pname} for pid, pname in programas_set.items()],
        key=lambda x: x["nombre"]
    )

    # Build cohortes list (remove internal helper fields)
    cohortes_list = []
    for key in sorted(cohortes_data.keys()):
        cohort = cohortes_data[key]
        # Remove internal tracking fields
        del cohort["_docente_set"]
        del cohort["_materia_map"]

        # Sort sesiones by fecha
        cohort["sesiones"].sort(key=lambda s: s["fecha"])

        cohortes_list.append(cohort)

    output = {
        "semestre": "2026-2",
        "campus": campus_list,
        "programas": programas_list,
        "cohortes": cohortes_list,
    }

    # Write output
    with open(OUTPUT_PATH, "w", encoding="utf-8") as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    print(f"✅ data.json generated at: {OUTPUT_PATH}")
    print(f"   - {len(campus_list)} campus")
    print(f"   - {len(programas_list)} programas")
    print(f"   - {len(cohortes_list)} cohortes")

    total_materias = sum(len(c["materias"]) for c in cohortes_list)
    total_sesiones = sum(len(c["sesiones"]) for c in cohortes_list)
    total_docentes = sum(len(c["docentes"]) for c in cohortes_list)
    print(f"   - {total_docentes} docentes (total across cohortes)")
    print(f"   - {total_materias} materias (total across cohortes)")
    print(f"   - {total_sesiones} sesiones (total across cohortes)")


if __name__ == "__main__":
    main()
