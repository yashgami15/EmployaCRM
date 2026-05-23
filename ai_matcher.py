import sqlite3
import json
import os
import sys

def get_matches():
    db_path = os.path.join(os.path.dirname(__file__), 'data', 'app.db')
    if not os.path.exists(db_path):
        return {"error": "Database not found"}

    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()

    cursor.execute("SELECT id, full_name, role, skills, preferred_work_role_field, preferred_location, experience_type, expected_salary_month FROM candidates WHERE status != 'Hired'")
    candidates = [dict(row) for row in cursor.fetchall()]

    cursor.execute("SELECT id, company_name, job_role, category, expectation, area, budget, status FROM clients WHERE status != 'Closed'")
    clients = [dict(row) for row in cursor.fetchall()]

    conn.close()

    suggestions = []

    for client in clients:
        client_text = f"{client['job_role']} {client['category']} {client['expectation']} {client['area']}".lower()
        client_tokens = set(client_text.split())

        best_candidates = []

        for candidate in candidates:
            cand_text = f"{candidate['role']} {candidate['skills']} {candidate['preferred_work_role_field']} {candidate['preferred_location']} {candidate['experience_type']}".lower()
            cand_tokens = set(cand_text.split())

            if not cand_tokens or not client_tokens:
                continue

            overlap = cand_tokens.intersection(client_tokens)
            score = round((len(overlap) / max(1, len(client_tokens))) * 100)

            if score > 0:
                best_candidates.append({
                    "candidate_id": candidate["id"],
                    "candidate_name": candidate["full_name"],
                    "match_score": score,
                    "expected_salary": candidate["expected_salary_month"],
                    "skills": candidate["skills"],
                    "role": candidate["preferred_work_role_field"]
                })

        best_candidates = sorted(best_candidates, key=lambda x: x["match_score"], reverse=True)[:5]
        
        if best_candidates:
            suggestions.append({
                "client_id": client["id"],
                "company_name": client["company_name"],
                "job_role": client["job_role"],
                "area": client["area"],
                "suggested_candidates": best_candidates
            })

    return {"status": "success", "data": suggestions}

if __name__ == "__main__":
    result = get_matches()
    print(json.dumps(result))
