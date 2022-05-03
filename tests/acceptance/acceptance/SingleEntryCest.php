<?php

class SingleEntryCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginAsAdmin();
    }

    public function customSingleEntryTitle(AcceptanceTester $I)
    {
        $I->wantTo('Test that custom title is displayed when viewing single entry');

        $view_slug = 'single-entry-title';

        foreach ([1, 2, 3] as $entry_id) {
            $entry = GFAPI::get_entry($entry_id);

            $I->goToViewSingleEntry($view_slug, $entry_id);

            $I->see("Custom title – ${entry[1]}", '.entry-title');
        }
    }
}
