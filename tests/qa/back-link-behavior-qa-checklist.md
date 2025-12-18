# Back Link Behavior QA Checklist

**Feature**: Back Link Behavior setting for Views
**Version**: 2.31+

## Related Support Tickets

| Ticket | Description |
|--------|-------------|
| [#2761](https://secure.helpscout.net/conversation/101119615/2761) | Editing Entry takes user away from page w/ shortcode |
| [#2769](https://secure.helpscout.net/conversation/101377293/2769) | Goback link changes when Canceling edit |
| [#2940](https://secure.helpscout.net/conversation/106428000/2940) | New Bug on embedded view |
| [#3048](https://secure.helpscout.net/conversation/110764135/3048) | Cancel and Delete Buttons Goto Blank/Wrong URL |
| [#2486](https://secure.helpscout.net/conversation/93798534/2486) | Strange linking behavior from embedded view |
| [#7036](https://secure.helpscout.net/conversation/257164849/7036) | Back to Entries with filtered search results |
| [#7179](https://secure.helpscout.net/conversation/263424096/7179) | How to hide the "go back" link for certain view |
| [#7264](https://secure.helpscout.net/conversation/268348180/7264) | How can I remove the Back Link for Single Entry View? |

---

## Setup Requirements

Before testing, ensure you have:
- [ ] A View with multiple entries
- [ ] A page with the View embedded using shortcode (e.g., "Embedded View Page")
- [ ] The View also accessible via its own permalink (View CPT page)
- [ ] Test entries you can edit (ensure Edit Entry is enabled)

---

## Test Scenarios

### 1. Embedded View - Back Link Returns to Embedding Page

**Scenario**: User views a View that's embedded on a regular page, clicks into single entry, then clicks Back Link.

**Steps**:
- [ ] Create a page called "Directory Page" and embed a View using shortcode
- [ ] Visit the "Directory Page"
- [ ] Click on an entry to view Single Entry
- [ ] Verify the URL contains `gv_back={page_id}` parameter
- [ ] Click the Back Link
- [ ] **Expected**: Returns to "Directory Page", NOT the View's CPT permalink

**Related**: [#2761](https://secure.helpscout.net/conversation/101119615/2761), [#2486](https://secure.helpscout.net/conversation/93798534/2486), [#2940](https://secure.helpscout.net/conversation/106428000/2940)

---

### 2. View CPT Page - Back Link Returns to View

**Scenario**: User accesses View directly via its CPT permalink, views single entry, clicks Back Link.

**Steps**:
- [ ] Visit the View directly at its CPT URL (e.g., `/view/my-view/`)
- [ ] Click on an entry to view Single Entry
- [ ] Verify the URL does NOT contain `gv_back` parameter
- [ ] Click the Back Link
- [ ] **Expected**: Returns to the View's directory listing

---

### 3. Edit Entry Cancel Button - Returns to Correct Page

**Scenario**: User clicks edit, makes changes (or not), clicks Cancel.

**Steps**:
- [ ] Visit an embedded View on a page
- [ ] Click into Single Entry view
- [ ] Click Edit Entry link
- [ ] Click Cancel button
- [ ] **Expected**: Returns to the embedding page (not the View CPT)

**Related**: [#2769](https://secure.helpscout.net/conversation/101377293/2769), [#3048](https://secure.helpscout.net/conversation/110764135/3048)

---

### 4. Edit Entry with Multiple Updates - Cancel Still Works

**Scenario**: User updates entry multiple times, then clicks Cancel.

**Steps**:
- [ ] Visit an embedded View
- [ ] Click Edit Entry
- [ ] Make a change and click Update
- [ ] Make another change and click Update again
- [ ] Click Cancel
- [ ] **Expected**: Returns to original embedding page (not stuck in edit loop)

**Related**: [#2769](https://secure.helpscout.net/conversation/101377293/2769)

---

### 5. Back Link Behavior: "Directory" Setting

**Scenario**: View setting is set to "Return to directory".

**Steps**:
- [ ] Edit the View settings
- [ ] Set "Back Link Behavior" to "Return to directory"
- [ ] Save the View
- [ ] Visit Single Entry from an embedded page
- [ ] Click the Back Link
- [ ] **Expected**: Returns to the View's directory URL (not the embedding page)

---

### 6. Back Link Behavior: "Hidden" Setting

**Scenario**: View setting is set to "Hide link".

**Steps**:
- [ ] Edit the View settings
- [ ] Set "Back Link Behavior" to "Hide link"
- [ ] Save the View
- [ ] Visit Single Entry view
- [ ] **Expected**: Back Link is NOT displayed in Single Entry
- [ ] Verify Edit Entry Cancel button still works (it should still function)

**Note**: "Hidden" only hides the Back Link, not the Cancel button.

**Related**: [#7179](https://secure.helpscout.net/conversation/263424096/7179), [#7264](https://secure.helpscout.net/conversation/268348180/7264)

---

### 7. Search Filters Preserved with Directory Setting

**Scenario**: User searches/filters entries, clicks into entry, clicks Back Link.

**Steps**:
- [ ] Visit an embedded View with search bar
- [ ] Enter a search term and filter results
- [ ] Click on an entry from filtered results
- [ ] With "Directory" setting: Click Back Link
- [ ] **Expected**: Returns to directory with search filters preserved in URL

**Related**: [#7036](https://secure.helpscout.net/conversation/257164849/7036)

---

### 8. Browser Back Button Behavior

**Scenario**: Verify browser back button works alongside our Back Link.

**Steps**:
- [ ] Visit embedded View on a page
- [ ] Click into Single Entry
- [ ] Use browser's Back button (not GravityView Back Link)
- [ ] **Expected**: Returns to embedding page correctly
- [ ] Visit Single Entry again, click GravityView Back Link
- [ ] **Expected**: Also returns to embedding page

---

### 9. Invalid/Deleted Source Page

**Scenario**: The embedding page no longer exists.

**Steps**:
- [ ] Note the post ID of your embedding page
- [ ] Visit Single Entry from that page (URL will have `gv_back={id}`)
- [ ] Delete or trash the embedding page
- [ ] Click the Back Link
- [ ] **Expected**: Falls back gracefully (uses referer or View directory)

---

### 10. Direct URL Access (No Referer)

**Scenario**: User accesses single entry via direct URL/bookmark.

**Steps**:
- [ ] Copy a Single Entry URL (without `gv_back` parameter)
- [ ] Open in new browser window/incognito (no referer)
- [ ] Click the Back Link
- [ ] **Expected**: Returns to View's directory listing (graceful fallback)

---

### 11. Multiple Views Embedded on Same Page

**Scenario**: Page has multiple Views, ensure back links go to correct page.

**Steps**:
- [ ] Create a page with two different Views embedded
- [ ] Click into Single Entry from View #1
- [ ] Verify `gv_back` parameter points to embedding page
- [ ] Click Back Link
- [ ] **Expected**: Returns to the embedding page
- [ ] Repeat for View #2
- [ ] **Expected**: Same behavior - returns to embedding page

---

### 12. Security: Non-Numeric gv_back Parameter

**Scenario**: Malicious user tries to inject non-numeric value.

**Steps**:
- [ ] Manually modify URL to have `gv_back=<script>alert('xss')</script>`
- [ ] Load the page
- [ ] Click Back Link
- [ ] **Expected**: Non-numeric value ignored, falls back to referer/directory

---

### 13. View in Widget/Sidebar

**Scenario**: View displayed via widget in sidebar.

**Steps**:
- [ ] Add a GravityView widget to a sidebar
- [ ] Visit a page where the widget displays
- [ ] Click into Single Entry from the widget
- [ ] Click Back Link
- [ ] **Expected**: Appropriate back navigation (behavior depends on widget implementation)

---

## Regression Tests

- [ ] Verify existing Views without the new setting work correctly (defaults to "previous")
- [ ] Verify search widget functionality is not affected
- [ ] Verify pagination links still work correctly
- [ ] Verify sorting links still work correctly
- [ ] Verify entry link URLs are not excessively long

---

## Browser Testing

Test the above scenarios in:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

---

## Notes

- The `gv_back` parameter uses post ID (not full URL) for reliability and brevity
- Priority order: `gv_back` parameter → HTTP referer → View directory fallback
- "Hidden" setting hides the Back Link but Edit Entry Cancel still functions
- All `gv_back` values are validated: must be numeric and post must exist
