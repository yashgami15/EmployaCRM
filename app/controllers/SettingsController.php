<?php

declare(strict_types=1);

class SettingsController
{
    public static function index(): void
    {
        require_permission('settings');

        $user = current_user();
        $flash = get_flash();

        $groups = [
            'candidate_status' => 'Candidate Status',
            'candidate_source' => 'Candidate Source',
            'client_status' => 'Client Status',
            'interview_stage' => 'Interview Stage',
            'interview_mode' => 'Interview Mode',
            'experience_type' => 'Experience Type',
            'marital_status' => 'Marital Status',
            'documents' => 'Documents',
            'client_category' => 'Client Category',
            'client_timing' => 'Client Timing',
            'client_gender' => 'Client Gender Preference'
        ];

        $settings = [];
        foreach ($groups as $groupKey => $groupLabel) {
            $settings[$groupKey] = get_dynamic_options($groupKey);
        }

        require BASE_PATH . '/app/views/dashboard/settings.php';
    }

    public static function save(): void
    {
        require_permission('settings');
        verify_csrf();

        $groupData = $_POST['settings'] ?? [];

        if (is_array($groupData)) {
            $pdo = db();
            $pdo->beginTransaction();

            try {
                // Delete existing settings for the groups we're saving
                foreach (array_keys($groupData) as $groupName) {
                    $stmt = $pdo->prepare('DELETE FROM dropdown_settings WHERE group_name = :group');
                    $stmt->execute(['group' => $groupName]);
                }

                // Insert the new options
                $insert = $pdo->prepare('INSERT INTO dropdown_settings (group_name, option_value, sort_order) VALUES (:group_name, :option_value, :sort_order)');
                
                foreach ($groupData as $groupName => $optionsString) {
                    // Explode by comma or newline, trim, and remove empty values
                    $optionsArray = array_values(array_filter(array_map('trim', explode(',', str_replace("\n", ",", (string)$optionsString)))));
                    
                    foreach ($optionsArray as $index => $opt) {
                        $insert->execute([
                            'group_name' => $groupName,
                            'option_value' => $opt,
                            'sort_order' => $index
                        ]);
                    }
                }

                $pdo->commit();
                set_flash('Dropdown settings saved successfully.', 'success');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('Failed to save settings: ' . $e->getMessage(), 'danger');
            }
        }

        redirect('index.php?action=settings');
    }
}
