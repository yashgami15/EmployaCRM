<?php

declare(strict_types=1);

class AiMatcherController
{
    public static function index(): void
    {
        require_permission('ai_matcher');

        $user = current_user();
        $flash = get_flash();

        $tenantFilterWithAnd = (($_SESSION['role'] ?? 'user') !== 'admin') ? "AND tenant_name = '" . ($_SESSION['tenant_name'] ?? '') . "'" : "";

        $candidates = db()->query("SELECT id, full_name, role, skills_set as skills, preferred_work_role_field, preferred_location, experience_type, expected_salary_month FROM candidates WHERE status != 'Hired' $tenantFilterWithAnd")->fetchAll();
        $clients = db()->query("SELECT id, company_name, job_role, category, expectation, area, budget, status FROM clients WHERE status != 'Closed' $tenantFilterWithAnd")->fetchAll();

        $suggestions = [];

        foreach ($clients as $client) {
            $bestCandidates = [];

            foreach ($candidates as $candidate) {
                $matchData = match_score_detailed($candidate, $client);
                
                if ($matchData['score'] > 0) {
                    $bestCandidates[] = [
                        'candidate_id' => $candidate['id'],
                        'candidate_name' => $candidate['full_name'],
                        'match_score' => $matchData['score'],
                        'distance_km' => $matchData['distance_km'],
                        'expected_salary' => $candidate['expected_salary_month'] ?? '',
                        'skills' => $candidate['skills'] ?? '',
                        'role' => $candidate['preferred_work_role_field'] ?? ''
                    ];
                }
            }

            if (!empty($bestCandidates)) {
                usort($bestCandidates, static fn($a, $b) => $b['match_score'] <=> $a['match_score']);
                $bestCandidates = array_slice($bestCandidates, 0, 5);

                $suggestions[] = [
                    'client_id' => $client['id'],
                    'company_name' => $client['company_name'],
                    'job_role' => $client['job_role'] ?? '',
                    'area' => $client['area'] ?? '',
                    'suggested_candidates' => $bestCandidates
                ];
            }
        }

        require BASE_PATH . '/app/views/dashboard/ai_matcher.php';
    }
}
