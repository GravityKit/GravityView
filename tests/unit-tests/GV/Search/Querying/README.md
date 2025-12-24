# Search Querying Architecture

This document outlines the purpose and responsibilities of the new search querying classes that replace the legacy search filter conversion system.

---

## Overview

The search refactor introduces a clean separation of concerns between **parsing search input** and **building search criteria**. This architecture replaces the tightly coupled legacy implementation where request handling, filter building, and query generation were intermingled.

---

## Search_Request

**Namespace:** `GV\Search\Querying`
**Class Type:** Final, immutable value object
**Pattern:** Static factory methods with private constructor

### Purpose

The `Search_Request` represents the parsed search input from an HTTP request or programmatic call. It is responsible for:

1. **Detecting search requests** — Determining whether a given request contains search parameters.
2. **Extracting search arguments** — Parsing the raw request data (`$_GET`, `$_POST`, CLI arguments) into a normalized structure.
3. **Normalizing input keys** — Converting legacy prefixes (`filter_`, `input_`) and GravityView-specific keys (`gv_search`, `gv_by`, `gv_id`, `gv_start`, `gv_end`) into a consistent internal format.
4. **Preserving operators** — Extracting custom operators from companion keys (e.g., `filter_1|op`).
5. **Capturing the search mode** — Storing whether the search is `any` (OR) or `all` (AND).

### Responsibilities

- Parse and validate incoming request data.
- Normalize **primary search keys** to their canonical names (e.g., `gv_search` → `search_all`, `gv_id` → `entry_id`, `gv_by` → `created_by`).
- Normalize field identifiers (converting underscores to dots for sub-fields).
- Extract `field_id` and optional `form_id` from composite notation (`field_id:form_id`).
- Preserve the **raw operator** as provided (via `key|op` companion keys) — no validation.
- Preserve the **original request key** (`request_key`) for backwards-compatible filter hooks.
- Produce a transportable array representation via `to_array()`.

### Why Normalize Keys Here?

The request normalizes primary search keys (`gv_*` → canonical names) because:

1. **It's cheap** — Pure string manipulation with no external dependencies.
2. **It's structural** — These are vocabulary translations, not semantic interpretations.
3. **It enables migration** — The `gv_` prefix is legacy; the canonical names (`entry_id`, `search_all`, `created_by`) represent the target API.

Field-specific transformations (operator inference, value formatting) are deliberately **not** performed here because they require Form and Field metadata, which would couple the value object to the data layer.

### Boundaries

The `Search_Request` class does **NOT**:

- Validate whether fields are searchable in a specific View.
- Infer operators based on field type (e.g., `select` → `is`).
- Apply field-type-specific value transformations (e.g., date formatting).
- Build Gravity Forms search criteria or GF_Query conditions.
- Interact with the database, Form, or Field configuration.

### Creation Methods

- `from_request(Request $request)` — Creates from an HTTP or CLI request object.
- `from_arguments(array $arguments)` — Creates from a pre-populated arguments array.
- `is_search_request(Request $request)` — Static check without instantiation.

---

## Search_Filter_Builder

**Namespace:** `GV\Search\Querying`
**Class Type:** Final, singleton service
**Pattern:** Singleton with lazy initialization

### Purpose

The `Search_Filter_Builder` transforms a `Search_Request` into the format required by Gravity Forms search APIs. It is responsible for:

1. **Validating searchable fields** — Ensuring only configured searchable fields are processed.
2. **Transforming filter keys** — Converting normalized keys to GF-compatible keys (e.g., `entry_id` → `id`).
3. **Building search criteria** — Producing the `field_filters` array structure expected by Gravity Forms.
4. **Handling global search** — Processing quoted phrases, required terms, and excluded terms.
5. **Applying the search mode** — Setting the `mode` key on the resulting criteria.

### Responsibilities

- Filter out fields that are not searchable in the View context.
- Transform key names to match Gravity Forms conventions.
- **Infer and validate operators** based on field type and configuration.
- **Apply field-type-specific transformations** (date formatting, value normalization).
- Parse global search queries for advanced syntax (quotes, +/- operators).
- Handle View-specific settings (whitespace trimming).

### Why Field-Type Logic Lives Here

The builder performs context-aware transformations because:

1. **It requires Form/Field metadata** — Operator inference (e.g., `select` → `is`) depends on the field type.
2. **It's View-scoped** — Searchable field validation requires View configuration.
3. **It's the bridge** — Sits between the raw request and the GF query layer, making it the natural place for semantic interpretation.

The `Search_Request` provides "what the user asked for"; the builder interprets "what that means for this Form/View."

### Backwards Compatibility

The builder uses `request_key` (provided by `Search_Request`) when applying filter hooks like `gravityview/search/operator_allowlist`. This ensures existing hook implementations that reference legacy keys (`gv_search`, `gv_id`, `filter_1`, etc.) continue to work while the internal representation uses canonical names.

### Boundaries

The `Search_Filter_Builder` class does **NOT**:

- Parse raw HTTP request data (that is `Search_Request`'s job).
- Build `GF_Query_Condition` objects directly (future responsibility).
- Store state between calls (stateless transformations).

### Primary Method

- `to_search_criteria(Search_Request $request, ?View $view)` — Converts a search request to Gravity Forms search criteria, optionally scoped to a View's searchable fields.

---

## Relationship

```
HTTP Request / CLI / Arguments
         │
         ▼
   ┌─────────────────┐
   │  Search_Request │  ← Parses and normalizes input
   └────────┬────────┘
            │ to_array()
            ▼
   ┌─────────────────────┐
   │ Search_Filter_Builder│  ← Transforms to GF search criteria
   └────────┬─────────────┘
            │ to_search_criteria()
            ▼
   Gravity Forms Search Criteria Array
            │
            ▼
   (Future: QueryFilters / GF_Query)
```

---

## Design Principles

1. **Single Responsibility** — Each class has one clear purpose.
2. **Immutability** — `Search_Request` is immutable once created.
3. **Testability** — Both classes are easily unit testable in isolation.
4. **Framework Decoupling** — `Search_Request` has no View dependency; View context is injected into `Search_Filter_Builder`.
5. **Backward Compatibility** — The output format matches existing Gravity Forms expectations.

---

## Future Considerations

- The `Search_Filter_Builder` will eventually produce `QueryFilter` objects instead of raw arrays.
- Additional builder methods may be added for direct `GF_Query_Condition` generation.
- Field-type-specific handling (dates, products, checkboxes) will be encapsulated in dedicated handler classes.