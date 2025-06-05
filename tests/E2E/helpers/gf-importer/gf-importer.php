<?php
namespace GravityKit\GravityView\Tests\E2E\Helpers\GFImporter;
use GFAPI;
use WP_CLI;

function gf_form_exists_by_title( $title ) {
    $forms = GFAPI::get_forms();
    foreach ( $forms as $form ) {
        if ( $title === $form['title'] ?? '' ) {
            return $form['id'];
        }
    }
    return false; // Return false if no form with the given title exists
}

function transform_form_fields( $fields ) {
    $transformed_fields = [];

    foreach ( $fields as $field ) {
        $transformed_field = [
            'id'         => $field['id'],
            'type'       => $field['type'],
            'label'      => $field['label'],
            'isRequired' => $field['isRequired'] ?? false,
            'value'      => '',
        ];

        // Handle specific field types
        switch ( $field['type'] ) {
            case 'text':
            case 'email':
                $transformed_field['inputType'] = $field['type'];
                break;

            case 'select':
                if ( isset( $field['choices'] ) ) {
                    $transformed_field['choices'] = array_map( function ( $choice ) {
                        return [
                            'text'       => $choice,
                            'value'      => $choice,
                            'isSelected' => false,
                        ];
                    }, $field['choices'] );
                }
                break;

            case 'address':
                $transformed_field['inputs'] = [
                    [ 'id' => "{$field['id']}.1", 'label' => 'Street Address' ],
                    [ 'id' => "{$field['id']}.2", 'label' => 'Address Line 2' ],
                    [ 'id' => "{$field['id']}.3", 'label' => 'City' ],
                    [ 'id' => "{$field['id']}.4", 'label' => 'State / Province' ],
                    [ 'id' => "{$field['id']}.5", 'label' => 'ZIP / Postal Code' ],
                    [ 'id' => "{$field['id']}.6", 'label' => 'Country' ],
                ];
                break;

            case 'name':
                $transformed_field['inputs'] = [
                    [ 'id' => "{$field['id']}.3", 'label' => 'First' ],
                    [ 'id' => "{$field['id']}.6", 'label' => 'Last' ],
                ];
                break;

            // Add more complex field support here as needed
        }

        $transformed_fields[] = $transformed_field;
    }

    return $transformed_fields;
}

if ( ! class_exists( 'GFAPI' ) ) {
    wp_die( 'Gravity Forms is not activated or the GFAPI class is not available.' );
}

$data_dir = plugin_dir_path( __FILE__ ) . 'data';
if ( ! is_dir( $data_dir ) ) {
    wp_die( 'Forms and entries directory does not exist.' );
}

$data_files = glob( "{$data_dir}/*.json" );
if ( empty( $data_files ) ) {
    wp_die( 'No JSON files found in the forms and entries directory.' );
}

$imported_count = 0;
$skipped_count = 0;

foreach ( $data_files as $file ) {
    $decoded_data = json_decode( file_get_contents( $file ), true );
    
    if ( empty( $decoded_data['form']['title'] ) ) {
        WP_CLI::error(
            sprintf(
                "Invalid or missing form data in '%s'.",
                basename( $file )
            )
        );
    }
    
    $form_title = $decoded_data['form']['title'];
    
    if ( gf_form_exists_by_title( $form_title ) ) {
        WP_CLI::log( "Skipping duplicate form '{$form_title}'." );
        $skipped_count++;
        continue;
    }
    
    // Transform the form fields before importing
    $form_data = $decoded_data['form'];
    if ( isset( $form_data['fields'] ) ) {
        $form_data['fields'] = transform_form_fields( $form_data['fields'] );
    }
    
    $form_id = GFAPI::add_form( $form_data );
    
    if ( is_wp_error( $form_id ) ) {
        WP_CLI::error(
            sprintf( "Could not import '%s' from '%s': %s",
                $form_title,
                basename( $file ),
                $form_id->get_error_message()
            )
        );
        continue;
    }
    
    $imported_count++;
    WP_CLI::success( "Imported '{$form_title}' (#{$form_id})." );
    
    if ( empty( $decoded_data['entries'] ) || ! is_array( $decoded_data['entries'] ) ) {
        continue;
    }
    
    foreach ( $decoded_data['entries'] as $entry ) {
        $entry['form_id'] = $form_id;
        
        if ( isset( $entry['submitted_on'] ) ) {
            $entry['date_created'] = $entry['submitted_on'];
        }
        
        if ( isset( $entry['is_starred'] ) ) {
            $entry['is_starred'] = (bool) $entry['is_starred'];
        }
        
        if ( isset( $entry['is_read'] ) ) {
            $entry['is_read'] = (bool) $entry['is_read'];
        }
        
        $result = GFAPI::add_entry( $entry );
        
        if ( is_wp_error( $result ) ) {
            WP_CLI::error(
                sprintf( "Could not import entry for form #%s from '%s': %s",
                    $form_id,
                    basename( $file ),
                    $result->get_error_message()
                )
            );
        }
    }
}

WP_CLI::log(
    WP_CLI::colorize(
        sprintf(
            "%%BSummary:%%n %d %s imported. %d %s skipped due to prior import.",
            $imported_count, $imported_count === 1 ? 'form' : 'forms',
            $skipped_count, $skipped_count === 1 ? 'form' : 'forms'
        )
    )
);