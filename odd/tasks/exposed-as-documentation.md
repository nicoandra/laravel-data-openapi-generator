# ExposedAs documentation

## Goal
Document the `ExposedAs` attribute where it is declared and in the public README, including usage examples and release-note wording for the branch.

## Scope
- `src/Attributes/ExposedAs.php`
- `README.md`
- No runtime behavior changes.

## Tasks
1. Add PHPDoc to `ExposedAs` describing supported targets, accepted value, validation, and generated-schema behavior.
2. Clarify README feature listing and class/property usage examples.
3. Provide release notes text and verify formatting/tests relevant to documentation-only changes.

## Release notes

Documented the new `ExposedAs` attribute for OpenAPI schema generation. It now has inline API documentation and README guidance for class-level and property-level usage with `#[ExposedAs('string')]`. The attribute changes the generated schema representation only; it does not alter runtime casting or transformation behavior.

## Acceptance criteria
- The attribute class explains what `ExposedAs` does and how its constructor value is constrained.
- README explains both class-level and property-level usage without implying runtime transformation.
- Release notes clearly summarize the new public API and migration impact.
- No runtime behavior changes are introduced.

## Verification evidence

- `php -n -l src/Attributes/ExposedAs.php` — passed.
- `git diff --check -- src/Attributes/ExposedAs.php README.md` — passed.
- Independent documentation review — passed; parameter-target limitation is explicitly documented.
