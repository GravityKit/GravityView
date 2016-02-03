<?php
/**
 * @file class-gravityview-item-setting-quiz-use-max-score.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Quiz_Use_Max_Score extends GravityView_Item_Setting {

    var $name = 'quiz_use_max_score';

    var $type = 'checkbox';

    var $value = true;

    public function __construct() {

        $this->label = esc_html__(  'Show Max Score?', 'gravityview' );
        $this->description = esc_html__( 'Display score as the a fraction: "[score]/[max score]". If unchecked, will display score.', 'gravityview' );

        parent::__construct();

    }

}
