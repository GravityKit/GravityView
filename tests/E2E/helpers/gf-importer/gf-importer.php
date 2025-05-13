<?php
namespace GravityKit\GravityView\Tests\E2E\Helpers\GFImporter;

use GFAPI;

function gf_form_exists_by_title($title)
{
    $forms = GFAPI::get_forms();

    foreach ($forms as $form) {
        if ($title === $form['title'] ?? '') {
            return $form['id'];
        }
    }

    return false;
}

function gf_import_forms_and_entries()
{
    if (!class_exists('GFAPI')) {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::error('Gravity Forms is not activated or the GFAPI class is not available.');
        } else {
            wp_die('Gravity Forms is not activated or the GFAPI class is not available.');
        }
        return;
    }

    $data_dir = plugin_dir_path(__FILE__) . 'data';

    if (!is_dir($data_dir)) {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::error('Forms and entries directory does not exist.');
        } else {
            wp_die('Forms and entries directory does not exist.');
        }
        return;
    }

    $data_files = glob("{$data_dir}/*.json");

    if (empty($data_files)) {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::error('No JSON files found in the forms and entries directory.');
        } else {
            wp_die('No JSON files found in the forms and entries directory.');
        }
        return;
    }

    $imported_count = 0;
    $skipped_count = 0;

    foreach ($data_files as $file) {
        $decoded_data = json_decode(file_get_contents($file), true);

        if (empty($decoded_data['form']['title'])) {
            if (defined('WP_CLI') && WP_CLI) {
                \WP_CLI::error(sprintf("Invalid or missing form data in '%s'.", basename($file)));
            } else {
                wp_die(sprintf("Invalid or missing form data in '%s'.", basename($file)));
            }
            continue;
        }

        $form_title = $decoded_data['form']['title'];

        if (gf_form_exists_by_title($form_title)) {
            if (defined('WP_CLI') && WP_CLI) {
                \WP_CLI::log("Skipping duplicate form '{$form_title}'.");
            }
            $skipped_count++;
            continue;
        }

        $form_id = GFAPI::add_form($decoded_data['form']);

        if (is_wp_error($form_id)) {
            if (defined('WP_CLI') && WP_CLI) {
                \WP_CLI::error(sprintf(
                    "Could not import '%s' from '%s': %s",
                    $form_title,
                    basename($file),
                    $form_id->get_error_message()
                ));
            } else {
                wp_die(sprintf(
                    "Could not import '%s' from '%s': %s",
                    $form_title,
                    basename($file),
                    $form_id->get_error_message()
                ));
            }
            continue;
        }

        $imported_count++;

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Imported '{$form_title}' (#{$form_id}).");
        }

        if (empty($decoded_data['entries']) || !is_array($decoded_data['entries'])) {
            continue;
        }

        foreach ($decoded_data['entries'] as $entry) {
            $entry['form_id'] = $form_id;

            if (isset($entry['submitted_on'])) {
                $entry['date_created'] = $entry['submitted_on'];
            }

            if (isset($entry['is_starred'])) {
                $entry['is_starred'] = (bool)$entry['is_starred'];
            }

            if (isset($entry['is_read'])) {
                $entry['is_read'] = (bool)$entry['is_read'];
            }

            $form = GFAPI::get_form($form_id);
            foreach ($form['fields'] as $field) {
                if ($field->type === 'fileupload' && isset($entry[$field->id])) {
                    if ($field->multipleFiles && is_array($entry[$field->id])) {
                        $entry[$field->id] = json_encode($entry[$field->id]);
                    }
                }
            }

            $result = GFAPI::add_entry($entry);

            if (is_wp_error($result)) {
                if (defined('WP_CLI') && WP_CLI) {
                    \WP_CLI::error(sprintf(
                        "Could not import entry for form #%s from '%s': %s",
                        $form_id,
                        basename($file),
                        $result->get_error_message()
                    ));
                } else {
                    wp_die(sprintf(
                        "Could not import entry for form #%s from '%s': %s",
                        $form_id,
                        basename($file),
                        $result->get_error_message()
                    ));
                }
            }
        }
    }

    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::log(\WP_CLI::colorize(sprintf(
            "%%BSummary:%%n %d %s imported. %d %s skipped due to prior import.",
            $imported_count,
            $imported_count === 1 ? 'form' : 'forms',
            $skipped_count,
            $skipped_count === 1 ? 'form' : 'forms'
        )));
    }

    return [
        'imported' => $imported_count,
        'skipped' => $skipped_count
    ];
}

$current_command = isset($GLOBALS['argv']) ? implode(' ', array_slice($GLOBALS['argv'], 1)) : '';

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('gf import', function () {
        gf_import_forms_and_entries();
    });

    // Only execute immediately if we're running through wp eval-file
    if (!preg_match('/gf\s+import/', $current_command)) {
        gf_import_forms_and_entries();
    }
}

add_action('init', function () {
    if (isset($_GET['import_gf_forms_entries']) && $_GET['import_gf_forms_entries'] === 'true') {
        gf_import_forms_and_entries();
    }
});
