# Skillms HR Feature Restoration Plan

Source document: *Skillms: Guide de l'équipe RH* (French HR team guide, 1.11.x era)
All legacy files live under `main/admin/organization/` in the local `1.11.x` branch
(tracks `origin/1.11.x`). Use `git show 1.11.x:main/admin/organization/<file>.php`
to read any legacy file. **Never use the `upstream` remote** — it is not an accurate
source for this project. Use `origin` only.

---

## Naming conventions for all restored features

### Legacy database access
The 1.11.x database is available locally for schema and data inspection:
- **Host:** localhost
- **Database:** `skillms`
- **User:** `skillms` / **Password:** `skillms`

The primary source for table names, column types, and foreign key structures is
the legacy PHP code in the `1.11.x` branch (`git show 1.11.x:main/admin/organization/<file>.php`).
The live DB is a last-resort fallback when the PHP code is ambiguous. Example:
```bash
mysql -u skillms -pskillms skillms -e "DESCRIBE skill_benefit;"
mysql -u skillms -pskillms skillms -e "SHOW CREATE TABLE skill_job_offer\G"
```

### Table names
Legacy 1.11.x tables might have been prefixed with `skill_` (e.g. `skill_benefit`,
`skill_job_offer`, `skill_periodicity`). If that's the case, **drop the `skill_` 
prefix** in 2.0 for any table that is not specifically about a skill entity. Use 
only the bare entity name (e.g. `benefit`, `job_offer`, `periodicity`).

### Entity class names
If a legacy entity class name carries an unnecessary `Skill` prefix (e.g.
`SkillBenefit`, `SkillJobOffer`), drop it. Use the plain domain name (`Benefit`,
`JobOffer`, `Periodicity`, etc.). Only classes that are genuinely about skills
themselves (e.g. `SkillLevel`, `SkillRelUser`) should retain the `Skill` prefix.

### Chamilo HR identification marker
Add the following single-line comment at the top of every new entity, repository,
and controller file that belongs to the HR extension (just below the namespace
declaration and before any `use` statements):

```php
// Chamilo HR extension
```

This makes it trivial to find all HR-related code with a simple project-wide grep.

### Database migrations
HR extension migrations live in a separate path from core Chamilo migrations:

| | Chamilo core | Chamilo HR extension |
|---|---|---|
| Directory | `src/CoreBundle/Migrations/Schema/V200/` | `src/CoreBundle/Migrations/Schema/HR/` |
| Namespace | `Chamilo\CoreBundle\Migrations\Schema\V200` | `Chamilo\CoreBundle\Migrations\Schema\HR` |
| Base class | `AbstractMigrationChamilo` | same — reuse it |
| Version naming | `VersionYYYYMMDDHHMMSS` | same format — no conflict (namespace differs) |

Both paths are registered in `config/packages/doctrine_migrations.yaml`.
`doctrine:migrations:migrate` picks up both automatically.

To **generate** an HR migration (always pass `--namespace`):
```bash
php bin/console doctrine:migrations:diff --namespace='Chamilo\CoreBundle\Migrations\Schema\HR'
```

To **run** all pending migrations (both core and HR):
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### Routes and Vue router paths
All new HR feature routes must use the `/hr/` prefix:
- Symfony controller routes: `#[Route('/hr/benefits', ...)]`, `#[Route('/hr/job-offers', ...)]`, etc.
- Data endpoints: append `-data` as usual, e.g. `/hr/benefits-data`.
- Vue router paths: `path: 'hr/benefits'`, `path: 'hr/job-offers'`, etc.
- Public pages that are genuinely anonymous (CSR page, public job offers) may use
  a top-level path without `/hr/` if it makes semantic sense (e.g.
  `/social-responsibility/`, `/job-offers/`), but all admin/authenticated HR pages
  must sit under `/hr/`.

The 1.11.x codebase used `admin/organization/` as its URL namespace. In 2.0 we
use `/hr/` because these features constitute an HR management extension, not a
generic organisation module.

---

## What is already in the 2.0 branch

| Feature | Status |
|---|---|
| Skills (hierarchy, wheel, ranking) | Partial — Skill entity + wheel + ranking views exist; level profiles UI, levels UI, activities, objectives, badge designer and course-linking UI are missing |
| BranchSync / BranchTransaction | Present but unrelated — these are data-sync entities, not HR succursales |
| Career / UserCareer entities | Stub only — no UI |
| Everything else in this plan | Not implemented |

---

## Dependency order

Steps 1, 2, 4, 8, 9 have no inter-dependencies and could be run in parallel.
Steps 3, 5, 6, 7, 10 must follow their declared prerequisites.

| # | Feature group | Entry file | Depends on |
|---|---|---|---|
| 1 | Skills infrastructure (level profiles, levels, activities, objectives, badge designer) | `main/admin/organization/skill.php` | — |
| 2 | Staff metadata (branches, statuses, contract types, geographic zones, units) | `main/admin/organization/user_type_branch.php` | — |
| 3 | Organization structure (functions, function-unit, positions, org chart, competency search) | `main/admin/organization/organization.php` | 1, 2 |
| 4 | Benefits management (tags, benefits, assignments, my benefits, notifications) | `main/admin/organization/benefit.php` | — |
| 5 | Evaluation / GPEC (periods, stages, templates, scheduled evals, execution, action plan) | `main/admin/organization/performance_appraisal.php` | 1, 3 |
| 6 | Recruitment (job offers, applications, selection tests, process tracking) | `main/admin/organization/job_offer_list.php` | 3 |
| 7 | Training ROI + surveys (ROI per course/person/unit, training needs, workplace surveys) | `main/admin/organization/roi_course.php` | 2 |
| 8 | Diversity management (criteria, guidelines/reports) | `main/admin/organization/diversity_criteria.php` | — |
| 9 | CSR / Social Responsibility (SDG guidelines, public page) | `main/admin/organization/social_responsibility_guidelines.php` | — |
| 10 | Career plan (admin overview + personal space) | `main/admin/organization/career_plan.php` | 1, 3 |

---

## Step 1 — Skills infrastructure

```
/restore-legacy-feature main/admin/organization/skill.php

Restore the missing Skills infrastructure sub-features that are absent from the
2.0 branch. The existing 2.0 branch already has: Skill entity, SkillProfile
entity, SkillLevelProfile entity, SkillRelUser, SkillRelCourse, SkillRelSkill,
SkillRelGradebook — so map carefully before creating new entities.

The following sub-features must be restored:

1. SKILL LEVEL PROFILES (skill_profile.php in 1.11.x) — named scales/profiles
   such as "3-level language scale" or "5-level behavioural scale". Each profile
   is a named container for ordered levels. Check whether SkillProfile already
   covers this before creating a new entity.

2. SKILL LEVELS (skill_level.php in 1.11.x) — individual named levels within a
   profile, e.g. Beginner / Intermediate / Advanced, each with a numeric order
   and optional description. Check whether SkillLevelProfile already covers this.

3. SKILL-TO-LEVEL-PROFILE ASSOCIATION — each Skill is linked to exactly one
   level profile so evaluators know which scale to apply. May already be a column
   on the Skill entity.

4. ACTIVITIES (activity.php in 1.11.x) — a simple CRUD list of named items
   representing day-to-day tasks that can be assigned to job functions and to
   evaluation interview templates. No entity for this appears to exist in 2.0.
   Legacy table is likely `skill_activity` or similar — check
   `git show 1.11.x:main/admin/organization/activity.php` for the exact table
   name.

5. OBJECTIVES (objective.php in 1.11.x) — same structure as activities but
   semantically distinct: measurable goals used in evaluation interview templates.
   No entity for this appears to exist in 2.0. Check
   `git show 1.11.x:main/admin/organization/objective.php` for the table name.

6. BADGE DESIGNER — visual badge creation tool linked to individual skills.
   The Skill entity already has badge-related fields; the missing piece is the
   admin UI (a canvas-based designer in 1.11.x). Restore as a Vue component.
   Check `git show 1.11.x:main/admin/organization/skill.php` for how the badge
   designer was integrated.

For each legacy file, read it with `git show 1.11.x:main/admin/organization/<file>.php`
to extract exact table names and field lists before designing entities.
Access: all sub-features are admin-only (ROLE_ADMIN).
```

---

## Step 2 — Staff metadata

```
/restore-legacy-feature main/admin/organization/user_type_branch.php

Restore the Staff Metadata management block. Five independent admin pages that
define categorisation/signaletic data for personnel. None of these exist in the
2.0 branch (note: the existing BranchSync/BranchTransaction entities are a
completely different "data synchronisation" concept — do not reuse them).

Read each legacy file with `git show 1.11.x:main/admin/organization/<file>.php`
to get exact table names and field lists before designing entities.

1. BRANCHES / SUCCURSALES (user_type_branch.php) — physical or virtual office
   locations. Fields: name, address (optional), latitude/longitude for map display
   (optional), geographic zone FK (optional). A branch can belong to one
   geographic zone. Used later in positions and org chart.
   Likely legacy table: `personal_agenda` or `branch` — read the file to confirm.

2. STAFF STATUSES (user_type_status.php) — simple named labels categorising
   employees (e.g. "Full-time", "Part-time", "Contractor"). Fields: name only.
   Linked to a user's extra profile or a dedicated junction table.

3. CONTRACT TYPES (user_type_contractual.php) — simple named labels for contract
   categories (e.g. "Permanent", "Fixed-term"). Fields: name only.

4. GEOGRAPHIC ZONES (user_type_geographic_zone.php) — regional groupings that
   contain branches. Fields: name, description (optional). A branch belongs to
   one zone; a zone has many branches.

5. BUSINESS UNITS / ORGANIZATIONAL UNITS (organizational_unit.php) — hierarchical
   department/team structure with self-referential parent FK. Fields: name,
   description, parent unit (nullable FK to self), primary branch (nullable FK).
   Units are the backbone of the org chart and positions in step 3.

All five are admin-only (ROLE_ADMIN). Each needs a simple Vue CRUD view (list +
create/edit form). Use BaseTable for lists.
Create a single migration covering all five new tables.
```

---

## Step 3 — Organization structure

```
/restore-legacy-feature main/admin/organization/organization.php

Restore the full Organization Structure management block. This depends on:
- Business Units entity from step 2 (organizational_unit)
- Branches entity from step 2 (user_type_branch)
- Geographic Zones entity from step 2 (user_type_geographic_zone)
- Skills + Level Profiles from step 1

Read each legacy file with `git show 1.11.x:main/admin/organization/<file>.php`
before designing entities or controllers.

Six sub-features to restore:

1. PROFESSIONAL FUNCTIONS (professional_function.php) — job function definitions.
   Fields: name, description, start date (optional), end date (optional), parent
   function (nullable self-FK for hierarchy, informational only in 1.11.x).
   Activities (from step 1) can be associated to a function.
   Likely legacy table: `skill_professional_function` or similar.

2. FUNCTION-UNIT ASSOCIATIONS (function_in_unit.php) — links one professional
   function to one organizational unit, optionally with a specific geographic zone.
   Skills and activities can also be assigned at this level.
   Fields: name, description, function FK, unit FK, geographic zone FK (optional).
   Likely legacy table: `skill_function_unit` or similar.

3. HIERARCHICAL UNIT-FUNCTION LIST (unit_function_list.php) — read-only view.
   Left panel: expandable tree of units with their associated functions.
   Right panel: dynamic pie/doughnut chart showing staff headcount distribution
   for the selected unit subtree. Clicking a unit in the left tree refreshes the
   chart. Use PrimeVue Chart component or Chart.js (check what is available in
   the 2.0 asset pipeline before adding a dependency).

4. POSITION MANAGEMENT / USER-TO-FUNCTION (user_to_function.php) — assigns a
   specific user to a specific function-unit association, creating a "position".
   Fields: user FK, function-unit association FK, start date, end date, branch FK
   (optional). The end date triggers automatic Symfony Mailer email reminders to
   HR when a position is about to expire.
   Likely legacy table: `skill_user_to_function` or similar.

5. ORGANIZATIONAL CHART (chart.php) — two-tab Vue view:
   Tab A: manager/unit hierarchy only (one box per unit with the responsible
   person's name).
   Tab B: all staff placed in their units.
   In 1.11.x this used jquery.orgchart (resources/js/jquery.orgchart.js in 1.11.x
   under main/admin/organization/resources/). In 2.0 use a suitable Vue-compatible
   org-chart library (e.g. vue3-org-chart or d3-based) or implement with nested
   Tailwind divs if the tree is shallow. The chart is publicly accessible at
   /organizational-chart/ when config keys skills_orga_unit_public and
   skills_orga_people_public are true; implement as a public Vue route with a
   SettingsManager check in the data controller.

6. COMPETENCY PROFILE SEARCH (team_skills_goals.php) — a search page with four
   independent modes in the left sidebar:
   a) Search by skill(s) + required level → returns matching staff list.
   b) Search by function-unit profile → returns staff whose skill set matches
      the skills assigned to that function-unit association.
   c) Compare two staff members side-by-side (show their skill sets).
   d) Compare one staff member against a function-unit profile: show required
      skills (blue panel) vs acquired skills with color coding: green = meets
      level, orange = below required level, red = skill missing entirely.
   All four modes are in one Vue component with conditional rendering per mode.

Access: all sub-features are admin-only (ROLE_ADMIN) except the public org-chart
route which has no auth requirement (confirm with SettingsManager guard).
```

---

## Step 4 — Benefits management

```
/restore-legacy-feature main/admin/organization/benefit.php

Restore the Benefits Management block. No dependencies on other steps — only
requires that users exist (User entity, already present).

Read each legacy file with `git show 1.11.x:main/admin/organization/<file>.php`
before designing entities.

Five sub-features:

1. BENEFIT TAGS (benefit_tag.php) — simple labels with a hex colour value for
   visual distinction. Fields: name, colour (hex string). Used to tag benefits.
   Likely legacy table: `skill_benefit_tag` or similar.

2. BENEFITS DEFINITION (benefit.php) — named benefit/perquisite records.
   Fields: name, description, internal score (integer, for budgeting — not shown
   to employees), duration text (optional, e.g. "valid for 3 months"), tags
   (M2M to benefit tags). Benefits and blâmes are strictly confidential.
   Likely legacy table: `skill_benefit` or similar.

3. ASSIGN BENEFITS (assign_benefit.php) — assigns a defined benefit to a
   specific user. Fields: benefit FK, user FK, economic value (decimal, may differ
   from the base score — e.g. a benefit costs more when given to an executive),
   availability start date, expiry date, justification comment.
   Likely legacy table: `skill_assigned_benefit` or similar.

4. ASSIGNED BENEFITS LIST + NOTIFICATION (assign_benefit.php list view +
   notify_benefit.php) — admin list of all assignments with filters. Each row
   has a "notify" action that sends a Symfony Mailer email to the beneficiary
   informing them of the benefit. Implement notify as a dedicated POST endpoint.

5. PERSONAL SPACE — MY BENEFITS (my_benefits.php) — user-facing Vue view in the
   personal menu showing only that user's own received benefits: benefit name,
   description, availability dates, justification. Access is strictly per-user
   (each user sees only their own); use IS_AUTHENTICATED_FULLY + filter by
   current user in the data endpoint.

Admin pages (1–4): ROLE_ADMIN.
Personal view (5): IS_AUTHENTICATED_FULLY, data filtered to current user.
Use BaseTable for all list views.
```

---

## Step 5 — Evaluation / GPEC

```
/restore-legacy-feature main/admin/organization/performance_appraisal.php

Restore the Evaluation / GPEC (Gestion Prévisionnelle des Emplois et Compétences)
module. This is the most complex block.

Prerequisites that must already exist:
- Skills, Skill Level Profiles, Skill Levels (from step 1)
- Activities, Objectives (from step 1)
- Professional Functions and Function-Unit Associations (from step 3)
- User entity (existing)

Read each legacy file with `git show 1.11.x:main/admin/organization/<file>.php`
before designing entities. Key files: periodicity.php, recruitment_stage.php,
performance_appraisal_template.php, performance_appraisal_template_list.php,
performance_appraisal.php, performance_appraisal_evaluate.php,
performance_appraisal_comments.php.

Seven sub-features:

1. PERIODICITIES (periodicity.php) — named evaluation rhythms as a number of days
   (e.g. 180 = semi-annual). Fields: name, days (integer).
   Likely legacy table: `skill_periodicity` or similar.

2. EVALUATION STAGES (recruitment_stage.php) — named stages shared with the
   recruitment module (e.g. "Entry evaluation", "Mid-term", "Exit interview").
   Informational only; no enforced ordering. Fields: name, description.
   Likely legacy table: `skill_recruitment_stage` or similar.

3. EVALUATION INTERVIEW TEMPLATES (performance_appraisal_template.php +
   performance_appraisal_template_list.php) — templates defining the composition
   of an evaluation. Each template has: name, periodicity FK, and a list of
   weighted items. Items are of three types (skill, activity, objective) each
   with a weight percentage. The sum of all weights across all items in a template
   must equal 100. UI: a form with three "+ Add" sections (one per type) that
   dynamically add rows with a dropdown and a percentage input.
   Likely legacy table: `skill_performance_appraisal_template` + a junction
   table for items. Read the PHP files to confirm exact structure.

4. SCHEDULED EVALUATIONS (performance_appraisal.php) — HR admin creates an
   evaluation record: evaluatee (user FK), evaluator(s) (one or more user FKs),
   template FK, stage FK, planned date. Upon save, a Symfony Messenger message
   (or cron-based approach — check how 1.11.x handled it) schedules 9 email
   reminders at: -14d, -7d, -3d, -1d, day-of, +1d, +3d, +7d, +14d relative to
   planned date. If still incomplete at +14d, a report email goes to HR admins.
   Once the evaluator submits the final evaluation, all pending reminders stop.

5. EVALUATION EXECUTION (performance_appraisal_evaluate.php) — the evaluator
   opens the evaluation form showing:
   a) A history chart: a line/bar chart of prior evaluation scores for this
      evaluatee (how well they met past goals). Use PrimeVue Chart.
   b) The evaluation form: for each skill item show the prior acquired level and
      input for new level + comment; for activities and objectives show checkboxes
      or rating inputs + comment.
   c) Two save buttons: "Save draft" (persists without notifying) and "Send to
      evaluatee" (locks evaluator's input and sends email to evaluatee).
   After sending, the evaluatee can submit a response comment
   (performance_appraisal_comments.php). The evaluator then sees the response
   and records a final score.

6. ACTION PLAN — at the close of an evaluation the evaluator defines new
   objectives for the next period. This is stored as part of the evaluation record
   and is always visible to the evaluatee from their personal space.

7. PERSONAL SPACE — MY EVALUATION INTERVIEWS — a Vue view in the user's personal
   menu with two tabs:
   Tab A "Evaluations of me": evaluations where the current user is the evaluatee.
   Tab B "Evaluations I must conduct": evaluations where the current user is an
   evaluator.
   Each row links to the evaluation execution form.

Admin scheduling view: ROLE_ADMIN.
Evaluation execution view: IS_AUTHENTICATED_FULLY, access limited to assigned
evaluator(s) or the evaluatee.
Personal space view: IS_AUTHENTICATED_FULLY, filtered to current user.
```

---

## Step 6 — Recruitment management

```
/restore-legacy-feature main/admin/organization/job_offer_list.php

Restore the full Recruitment Management module. This is a multi-actor, multi-page
workflow.

Prerequisites:
- Professional Functions (from step 3) — offers are linked to a function
- Skills assigned to functions (from steps 1 and 3)
- User entity (existing)
- Chamilo course+exercise system (existing) — used for selection tests

Read each legacy file with `git show 1.11.x:main/admin/organization/<file>.php`
before designing entities.

Key files to read: job_offer_list.php, job_offer_add.php, job_offer_edit.php,
job_offer_public_list.php, job_offer_page.php, job_offer_already_user.php,
job_offer_candidate_inscription.php, job_offer_candidate_applications.php,
job_offer_application.php, job_offer_application_list.php,
job_offer_application_page.php, job_offer_evaluate_candidate.php,
job_offer_quiz.php, recruitment_process.php, recruitment_process_stage.php.

Ten sub-features:

1. JOB OFFER CRUD (job_offer_list.php, job_offer_add.php, job_offer_edit.php) —
   fields: title, description, function FK (inherits required skills), publish
   flag (boolean), publication start date, publication end date.
   Likely legacy table: `skill_job_offer` or similar.

2. CANDIDATE APPLICATION (job_offer_application.php) — a user applies to an
   offer by uploading a CV file, motivation letter file (optional), salary
   expectation (optional decimal), and availability date (optional). Store file
   references using Chamilo's resource node system or as simple file uploads.
   Likely legacy table: `skill_job_offer_application` or similar.

3. CANDIDATE APPLICATION MANAGEMENT (job_offer_candidate_applications.php) —
   admin paginates through candidates for a given offer (candidate 1/3 with
   left/right arrows). Shows: candidate profile summary, CV download button,
   cover letter download button, required skills vs candidate's acquired skills,
   recruiter observations block, selection test scores block, an "Evaluate this
   candidate" button.

4. CANDIDATE EVALUATION FORM (job_offer_evaluate_candidate.php) — recruiter
   records public notes (visible to candidate) and private notes (admin only)
   about the candidate's CV, cover letter, and interview impressions.

5. SELECTION TESTS (job_offer_quiz.php) — links an existing Chamilo
   course+exercise to a job offer as a selection test. When a candidate applies
   they see a button to take the test. After completion, their score is stored.
   Admins see all candidates' scores in a summary table with average score.
   Read how 1.11.x stored the course+exercise reference in the offer record.

6. RECRUITMENT PROCESSES (recruitment_process.php) — a simple record linking a
   registered user to an open selection process. Fields: user FK, offer FK,
   creation date. Serves as a reference for process tracking.

7. RECRUITMENT PROCESS TRACKING (recruitment_process_stage.php) — records
   specific interview appointments. Fields: datetime, stage FK (from step 5's
   stages), recruitment process FK, supervisor user FK, description/notes.

8. PUBLIC JOB OFFER LIST (/job-offers route, job_offer_public_list.php) —
   publicly accessible (no auth) Vue page listing active published offers.
   Needs both an IndexController route and a Vue router entry.

9. PUBLIC JOB OFFER DETAIL (/job-offers/{id} route, job_offer_page.php) —
   publicly accessible detail page showing full offer with required skills,
   activities, and an "Apply" button. Anonymous users clicking Apply are directed
   to register or login first.

10. PERSONAL SPACE — MY APPLICATIONS (job_offer_application_list.php +
    job_offer_application_page.php) — Vue view in user's personal menu listing
    their own applications with status, offer title, application date, and test
    score if applicable.

Admin views (1, 3, 4, 5, 6, 7): ROLE_ADMIN.
Public views (8, 9): no auth restriction.
Application submission (2): IS_AUTHENTICATED_FULLY.
Personal space (10): IS_AUTHENTICATED_FULLY, filtered to current user.
```

---

## Step 7 — Training ROI

```
/restore-legacy-feature main/admin/organization/roi_course.php

Restore the Training ROI management block.

Prerequisites:
- Organizational Units entity from step 2
- Existing Chamilo course and session infrastructure (already present in 2.0)

Read each legacy file with `git show 1.11.x:main/admin/organization/<file>.php`.
Key files: roi_course.php, roi_person.php, roi_unit.php. Also check whether
training-needs diagnostic surveys and workplace surveys are implemented as
wrappers in this directory or rely on main/survey/ — read the 1.11.x admin index
to find their links.

Five sub-features:

1. ROI PER COURSE (roi_course.php) — lists sessions/courses filtered by date
   range. For each: session name, start/end date, enrolled learner count, total
   cost (editable by HR — stored separately from Chamilo's session record), and
   computed cost-per-learner (total cost ÷ learner count). HR must be able to
   enter/edit the cost per session inline or via a form.
   The cost likely lives in a new table (e.g. `skill_session_cost`) since the
   existing Chamilo session entity has no cost field. Confirm by reading
   roi_course.php.

2. ROI PER PERSON / TRAINING PASSPORT (roi_person.php) — filtered by user and
   optional date range: shows all courses/sessions completed by that user and
   the total investment (sum of session costs × their share). Output as a
   sortable table and optionally exportable.

3. ROI PER ORG UNIT (roi_unit.php) — same data aggregated by organizational unit.
   Requires that users are assigned to units via positions (step 3). Each unit
   row shows total training investment across all members.

4. TRAINING NEEDS DIAGNOSTIC SURVEYS — surveys distributed per org unit to
   collect staff training needs. Check whether 1.11.x implemented these as a
   separate page under main/admin/organization/ or via a link to main/survey/.
   Read main/admin/index.php in 1.11.x to find the exact URL:
   `git show 1.11.x:main/admin/index.php | grep -A2 "diagnostic\|training_need\|formation"`.
   Replicate using the existing 2.0 Chamilo survey infrastructure if possible,
   with org-unit distribution logic added on top.

5. WORKPLACE SURVEYS — identical mechanism to (4) but categorised separately for
   climate/satisfaction surveys. Same implementation, different category label.

Admin access only (ROLE_ADMIN) for all five sub-features.
```

---

## Step 8 — Diversity management

```
/restore-legacy-feature main/admin/organization/diversity_criteria.php

Restore the Diversity Management block.

No hard dependencies on other restoration steps, but requires:
- The existing Chamilo user extra-profile fields system (ExtraField entity and
  related infrastructure — already present in 2.0).

Read the legacy files:
`git show 1.11.x:main/admin/organization/diversity_criteria.php`
`git show 1.11.x:main/admin/organization/diversity_guidelines.php`

Two sub-features:

1. DIVERSITY CRITERIA (diversity_criteria.php) — each diversity criterion is
   linked to one user extra-profile field of type radio-button or dropdown (these
   are the only field types whose values can be meaningfully aggregated into a
   chart). The admin can either select an existing extra field or create a new one
   inline via an "Add profile field" button. The criterion has its own display
   title (may differ from the extra-field label).
   Important: criteria fields must be marked as not-visible-to-other-users in the
   extra-field definition to protect sensitive personal data (gender, disability,
   etc.).
   Likely legacy table: `skill_diversity_criteria` referencing the extra-field ID.
   Check the PHP file for exact table and field names.

2. DIVERSITY GUIDELINES / REPORTS (diversity_guidelines.php) — read-only page
   showing one pie or bar chart per configured diversity criterion. The chart data
   is built by aggregating the values that staff have entered in their user profile
   for the linked extra field. Use PrimeVue Chart component.
   If the extra field has not been filled in by many users, show a data-quality
   warning ("X% of staff have not filled in this criterion").

Admin access only (ROLE_ADMIN) for both sub-features.
```

---

## Step 9 — CSR / Social Responsibility

```
/restore-legacy-feature main/admin/organization/social_responsibility_guidelines.php

Restore the Social Responsibility (CSR) module. Standalone — no dependencies on
other restoration steps.

Read the legacy files:
`git show 1.11.x:main/admin/organization/social_responsibility_guidelines.php`
`git show 1.11.x:main/admin/organization/social_responsibility_guidelines_page.php`

Two sub-features:

1. CSR GUIDELINES ADMIN (social_responsibility_guidelines.php) — admin form
   covering all 17 UN Sustainable Development Goals (SDGs). For each SDG the
   admin can enter: the organisation's position/description text (rich text or
   plain textarea), and toggle "Published" (boolean). The system must keep a
   history of changes over time (timestamped versions or at minimum an
   `updated_at` timestamp per SDG row).
   The 17 SDG titles (and optionally their official icons/colours) are fixed UN
   definitions and should be seeded in the migration or in a fixture. Only the
   description text and published flag are editable at runtime.
   Likely legacy table: `skill_social_responsibility` with one row per SDG (17
   rows seeded on install). Check the PHP file to confirm the exact structure.

2. PUBLIC CSR PAGE (/social-responsibility/ route,
   social_responsibility_guidelines_page.php) — publicly accessible Vue page
   (no auth required) listing all SDG entries where published = true. Shows the
   SDG number, title, and the organisation's description text.
   Must be registered in both IndexController (Symfony route) and the Vue router.

Admin view (1): ROLE_ADMIN.
Public view (2): no authentication required.
```

---

## Step 10 — Career plan

```
/restore-legacy-feature main/admin/organization/career_plan.php

Restore the Career Plan feature.

Prerequisites (must be completed first):
- Skills, Skill Level Profiles, Skill Levels (step 1)
- Organizational Units (step 2)
- Professional Functions, Function-Unit Associations, Positions / User-to-Function
  (step 3)
- Skill assignments to users (SkillRelUser, from step 1)
- Skill assignments to function-unit associations (from step 3)

Read the legacy files:
`git show 1.11.x:main/admin/organization/career_plan.php`
`git show 1.11.x:main/admin/organization/user_career_plan.php`
Also read the Twig templates:
`git show 1.11.x:main/template/default/admin/organization/career_plan.tpl`
`git show 1.11.x:main/template/default/admin/organization/user_career_plan.tpl`

Two sub-features:

1. HR ADMIN CAREER PLAN OVERVIEW (career_plan.php) — admin view showing the
   configured career paths in the system. Read the legacy PHP to understand
   exactly what data it displays (it may simply be a list of function-unit
   associations ordered by hierarchy level, showing the skills required at each
   level). Replicate the same scope in the 2.0 Vue admin view.

2. PERSONAL SPACE — MY CAREER PLAN (user_career_plan.php) — the most important
   view. Algorithm:
   a) Find the current user's position (via User-to-Function assignment).
   b) Find all positions hierarchically above the user's current unit in the org
      tree (parents, grandparents, and sibling units that share skill requirements).
   c) For each candidate target position, show the required skills (from the
      function-unit association's skill assignments) alongside the user's own
      acquired skill levels (from SkillRelUser).
   d) Render as stacked bars per skill: a brown/grey bar showing the required
      level and a green bar overlaid showing the acquired level, so the user can
      see their gap at a glance.
   e) If a user has no current position assigned, show an appropriate empty state.

   The page is in the user's personal menu. Access: IS_AUTHENTICATED_FULLY,
   filtered to current user's data only.

Admin view: ROLE_ADMIN.
Personal view: IS_AUTHENTICATED_FULLY, data scoped to current user.
No new database entities should be needed — this feature is a read-only
projection of data created in steps 1–3.
```
