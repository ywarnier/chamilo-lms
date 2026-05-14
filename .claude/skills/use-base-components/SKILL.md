---
name: use-base-components
description: >
  Replace native HTML form elements with Base* components in Vue files AND
  ensure every Base*/SectionHeader/Fieldset tag in any Vue file has a matching
  import in <script setup> (Vue 3 does not auto-register local components in
  this project; only Column is global). Auto-invoke when: user creates ANY
  new Vue file or page (form, dialog, view), adds Base*/SectionHeader/Fieldset
  tags to an existing Vue file, edits a Vue file containing native <input>,
  <select>, <textarea>, or <checkbox> elements, asks to refactor form fields,
  reports "Failed to resolve component" runtime warnings, or mentions
  BaseInputText, BaseSelect, BaseTextArea, BaseCheckbox, BaseCalendar,
  BaseColorPicker, BaseRadioButtons, BaseMultiSelect, BaseAutocomplete,
  BaseDialog, BaseTable, BaseButton, BaseInputNumber, SectionHeader, or any
  Base* component.
  Do NOT invoke for: non-Vue files, React or Angular components, styling-only
  changes inside files whose Base* imports are already complete.
allowed-tools:
  - Read
  - Edit
  - Write
  - Glob
  - Grep
  - Bash
---

Review the Vue file(s) referenced in `$ARGUMENTS` (or the currently open file if no argument is given)
and replace every native HTML form element with the appropriate `Base*` component from
`assets/vue/components/basecomponents/`. Follow the mapping and rules below exactly.

---

## ⚠ Critical rule (read first): every component used in the template must be imported

In this project, Vue 3 with `<script setup>` does **NOT** auto-register local components.
Only `Column` (PrimeVue) is registered globally in `assets/vue/main.js:48,206`. **Every other
component** — `BaseButton`, `BaseDialog`, `BaseSelect`, `BaseTable`, `BaseInputText`,
`BaseInputNumber`, `BaseCheckbox`, `BaseCalendar`, `BaseAutocomplete`, `BaseMultiSelect`,
`BaseRadioButtons`, `BaseColorPicker`, `BaseTextArea`, `BaseIcon`, `SectionHeader`, `Fieldset`,
etc. — **MUST be imported explicitly** in `<script setup>`.

**Why this rule exists**: when a component is missing its import, Vue 3 does **NOT** raise a
build error. `yarn build` reports `webpack compiled successfully` even with missing imports.
The failure surfaces only at runtime as `[Vue warn]: Failed to resolve component: BaseDialog`,
and that warning does not show in CI. So you cannot rely on `yarn build` to catch this — you
must cross-check by hand (or via the verification command at the end of this skill).

When you **create** a new Vue file or **add** a component tag to an existing one, the imports
are part of the same change. Don't defer them. Don't rely on the file already having the
imports from a prior edit. Verify every time.

Standard import paths (note: from `assets/vue/views/<feature>/Foo.vue` the prefix is `../../`;
from `assets/vue/views/<feature>/<sub>/Foo.vue` it is `../../../`):

```js
// Most common Base* components
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton       from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar     from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox     from "../../components/basecomponents/BaseCheckbox.vue"
import BaseColorPicker  from "../../components/basecomponents/BaseColorPicker.vue"
import BaseDialog       from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon         from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber  from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText    from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect  from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseSelect       from "../../components/basecomponents/BaseSelect.vue"
import BaseTable        from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea     from "../../components/basecomponents/BaseTextArea.vue"

// Layout
import SectionHeader from "../../components/layout/SectionHeader.vue"

// PrimeVue (when needed alongside Base* — Column is global so do NOT import)
import Fieldset from "primevue/fieldset"
```

**Exceptions**: only `Column` (used inside `<BaseTable>`) is auto-registered globally —
do NOT import it.

---

## Mapping: native element → Base* component

| Native element                     | Base* component    | Import path                                            |
|------------------------------------|--------------------|--------------------------------------------------------|
| `<input type="text">`              | `BaseInputText`    | `../../components/basecomponents/BaseInputText.vue`    |
| `<textarea>`                       | `BaseTextArea`     | `../../components/basecomponents/BaseTextArea.vue`     |
| `<input type="number">`            | `BaseInputNumber`  | `../../components/basecomponents/BaseInputNumber.vue`  |
| `<select>` + `<option>`            | `BaseSelect`       | `../../components/basecomponents/BaseSelect.vue`       |
| `<input type="date">`              | `BaseCalendar`     | `../../components/basecomponents/BaseCalendar.vue`     |
| `<input type="checkbox">` (binary) | `BaseCheckbox`     | `../../components/basecomponents/BaseCheckbox.vue`     |
| `<input type="radio">` group       | `BaseRadioButtons` | `../../components/basecomponents/BaseRadioButtons.vue` |
| Multi-select list                  | `BaseMultiSelect`  | `../../components/basecomponents/BaseMultiSelect.vue`  |
| Search-with-suggestions            | `BaseAutocomplete` | `../../components/basecomponents/BaseAutocomplete.vue` |
| `<input type="color">`             | `BaseColorPicker`  | `../../components/basecomponents/BaseColorPicker.vue`  |

Remove the surrounding `<div><label>…</label><input>…</div>` wrapper — every Base* component
renders its own label internally.

---

## Critical rule: label translation

Components differ in whether they call `t()` internally:

| Component      | Internal `t()`?            | How to pass label                              |
|----------------|----------------------------|------------------------------------------------|
| `BaseTextArea` | **Yes** (`{{ t(label) }}`) | `label="Raw key"` — never wrap in `t()`        |
| All others     | No                         | `:label="t('Raw key')"` — always wrap in `t()` |

Passing `t('Key')` to `BaseTextArea` will attempt to translate an already-translated string and
break non-English locales.

---

## Component APIs

### BaseInputText
```vue
<BaseInputText
  id="field-id"
  v-model="form.title"
  :label="t('Title')"
  name="title"
/>
```
**Props:** `id` (required), `label` (required), `modelValue` (String|null, required),
`name`, `errorText`, `isInvalid`, `required`, `helpText`, `formSubmitted`, `disabled`.
Has `inheritAttrs: false` with `v-bind="$attrs"` on the inner input → extra attrs and
events (`@input`, `@blur`, `autocomplete`, `placeholder`, etc.) are forwarded.

---

### BaseTextArea
```vue
<BaseTextArea
  id="field-id"
  v-model="form.description"
  label="Description"
  name="description"
  rows="3"
/>
```
**Props:** `id` (required), `label` (required, raw key), `modelValue` (String, required),
`errorText`, `isInvalid`.
Extra attrs (`rows`, `name`, etc.) forwarded via `v-bind="$attrs"`.

---

### BaseInputNumber
```vue
<BaseInputNumber
  id="field-id"
  v-model="form.score"
  :label="t('Score')"
  :min="0"
  :step="0.1"
  name="score"
/>
```
**Props:** `id` (required), `label` (required), `modelValue` (Number, required),
`step` (default 1), `min`, `max`, `isInvalid`, `errorText`, `disabled`, `helpText`.
Renders PrimeVue InputNumber with +/− spinner buttons. `name` is NOT forwarded — no
`v-bind="$attrs"` on this component.

---

### BaseSelect
```vue
<BaseSelect
  id="field-id"
  v-model="form.status"
  :label="t('Status')"
  :options="statusOptions"
  name="status"
/>
```
**Props:** `id`, `label` (required), `options` (Array, required), `optionLabel` (default `"label"`),
`optionValue` (default `"value"`), `name`, `placeholder`, `allowClear`, `hastEmptyValue`,
`isLoading`, `disabled`, `messageText`, `isInvalid`.
Uses `defineModel` — compatible with `v-model`.

**Options format** (default keys):
```js
const statusOptions = [
  { label: t('Active'), value: 'active' },
  { label: t('Inactive'), value: 'inactive' },
]
```
Or pass `:option-label="'title'"` `:option-value="'id'"` to use existing object keys directly.

Properties with `@` in the name (e.g. `@id`) are not safe as `optionValue` strings — map
them to plain keys in a computed property first:
```js
const compensationOptions = computed(() =>
  compensations.value.map((c) => ({ label: c.title, value: c['@id'] }))
)
```

Use `allow-cleared` for optional filters (adds a clear/× button). Use `:hast-empty-value="true"`
to prepend a `--` row when the field is required with a blank default.

**Para listas grandes (decenas/cientos de opciones) o anidadas**, usar `BaseAutocomplete` en
lugar de `BaseSelect` — ver la sección de [BaseAutocomplete](#baseautocomplete) y el patrón
de caché con `useEntityCache`.

---

### BaseCalendar
```vue
<BaseCalendar
  id="field-id"
  v-model="form.startDate"
  :label="t('Start date')"
/>
```
**Props:** `id` (required), `label` (required), `type` (`"single"` | `"range"`, default `"single"`),
`showTime`, `isInvalid`, `errorText`, `showInline`.
Uses `defineModel` — type `Date | Array | String | null`.

**⚠ Important:**
- Initialise the model value as `null` or `new Date()`, never as `""`.
- The model holds a `Date` object after user interaction. Serialise with
  `new Date(value).toISOString()` before sending to the API.
- For string comparisons in computed filters, convert first:
  ```js
  const dateStr = value instanceof Date ? value.toISOString().slice(0, 10) : String(value).slice(0, 10)
  ```
- After switching from `<input type="date">`, update `resetFilters` / initial form values to use
  `null` instead of `""`.

**Rango de fechas — `type="range"`:**
Cuando un formulario tiene un campo de inicio y uno de fin, usar un solo `BaseCalendar` con
`type="range"` en lugar de dos calendarios separados. El modelo es `[startDate, endDate | null]`:

```vue
<BaseCalendar
  id="date-range"
  v-model="dateRange"
  :label="t('Assignment period')"
  type="range"
/>
```

```js
const dateRange = ref([new Date(), null])   // inicializar con inicio = hoy, fin = null

// Al guardar, extraer por índice:
const payload = {
  startDate: dateRange.value?.[0] ? new Date(dateRange.value[0]).toISOString() : null,
  endDate:   dateRange.value?.[1] ? new Date(dateRange.value[1]).toISOString() : null,
}

// Al resetear:
dateRange.value = null   // o [new Date(), null] si quieres pre-rellenar el inicio
```

El segundo elemento es `null` hasta que el usuario selecciona la fecha de fin — comprueba
`dateRange.value?.[1]` antes de serializar.

---

### BaseCheckbox
```vue
<BaseCheckbox
  id="field-id"
  v-model="form.isActive"
  :label="t('Active')"
  name="is_active"
/>
```
**Props:** `id` (required), `name` (required), `label` (required).
Uses `defineModel` (Boolean) — binary checkbox only. For multi-value checkbox arrays
(e.g. selecting multiple IRIs), keep native `<input type="checkbox">` with `v-model` array
binding.

---

### BaseRadioButtons
```vue
<BaseRadioButtons
  v-model="form.type"
  :options="typeOptions"
  :title="t('Type')"
  name="type"
/>
```
**Props:** `modelValue` (String|Number, required), `name` (required),
`options` (Array `{ label, value }`, required), `title` (optional heading).
Uses traditional emit pattern (not `defineModel`) — `v-model` works as expected.
Translate option labels in the computed/data, not inside the component:
```js
const typeOptions = computed(() => [
  { label: t('Internal'), value: 'internal' },
  { label: t('External'), value: 'external' },
])
```

---

### BaseMultiSelect
```vue
<BaseMultiSelect
  v-model="form.tags"
  :options="tagOptions"
  :label="t('Tags')"
  input-id="field-id"
  option-label="title"
  option-value="id"
/>
```
**Props:** `modelValue` (Array, required), `options` (Array), `inputId` (required — note: `inputId`
not `id`), `label` (required), `optionLabel` (default `"name"`), `optionValue` (default `"id"`),
`isInvalid`, `errorText`, `isLoading`.
Renders chips for selected values.

---

### BaseAutocomplete
```vue
<BaseAutocomplete
  id="field-id"
  v-model="form.user"
  :label="t('User')"
  :search="searchUsers"
  option-label="fullName"
/>
```
**Props:** `id` (required), `label` (required), `search` (Function, required),
`optionLabel` (default `"name"`), `isMultiple`, `disabled`, `helpText`, `isInvalid`.

**Cuándo usar `BaseAutocomplete` en lugar de `BaseSelect`:**

- La lista tiene **decenas o cientos** de elementos — un `<select>` se vuelve incómodo.
- La fuente es un endpoint paginado o sin tope superior conocido (ej. `/api/users`).
- La lista es **anidada** (ej. árbol de skills) y el usuario necesita buscar por texto.

Para listas cortas y fijas (≤ ~20 ítems, ej. estados, periodicidades, tipos), seguir usando `BaseSelect`.

El prop `search` recibe el query y devuelve una Promesa con el array de sugerencias. Existen dos patrones según el tamaño del dataset:

**1) Búsqueda server-side** — datasets grandes o paginados:
```js
async function searchUsers(query) {
  const result = await baseService.getCollection('/api/users', { search: query, itemsPerPage: 10 })
  return result.items
}
```

**2) Búsqueda con caché en memoria — `useEntityCache`** (`assets/vue/composables/useEntityCache.js`):

Factory con `Map` a nivel de módulo. Cachea la lista por endpoint en toda la sesión — múltiples vistas / múltiples instancias del autocomplete comparten una sola petición.

```js
import { useEntityCache } from "../../composables/useEntityCache"

const skillsCache = useEntityCache("/api/skills", { pagination: false })

onMounted(async () => {
  await skillsCache.load()   // carga una vez por sesión
})
```

```vue
<BaseAutocomplete
  id="skill-ref"
  v-model="selectedSkill"
  :label="t('Skill')"
  :search="skillsCache.search"
  option-label="title"
/>
```

API del caché:
- `load()` — carga lazy una vez; desduplica llamadas concurrentes; en siguientes llamadas devuelve los items ya cacheados.
- `search(query)` — filtra in-memory por el `labelField` (3er argumento del factory, default `"title"`). Pasar como prop `search`.
- `findById(id)` — resuelve un id numérico al objeto. Útil en modo edición cuando el backend envía solo el id.
- `invalidate()` — limpia el caché. Llamar después de crear/editar/borrar la entidad para forzar recarga.

**Cuando el v-model debe enviarse como id (no IRI ni objeto)** — `BaseAutocomplete` mantiene el objeto completo en el modelo; al guardar se extrae `.id` y al cargar (edit) se resuelve id → objeto con `findById`:
```js
// addItem
form.items.push({ ref: null })

// load (modo edición)
items: backendItems.map((i) => ({ ref: skillsCache.findById(i.refId) }))

// save payload
items: form.items.filter((i) => i.ref?.id).map((i) => ({ refId: Number(i.ref.id) }))
```

**Backend — habilitar `pagination=false` cuando uses el caché**: las entidades cuyo `ApiResource` no declare `paginationClientEnabled: true` ignorarán el `pagination: false` del cliente y devolverán solo 30 ítems silenciosamente. Verifica el atributo de la entidad antes de cachear listas completas.

**Siempre usar `BaseAutocomplete` en lugar del patrón manual `<BaseInputText>` + `<ul>`.**
Cuando veas este patrón en un archivo Vue, reemplázalo:

```vue
<!-- ❌ patrón manual a reemplazar -->
<div class="relative">
  <BaseInputText v-model="userSearch" @input="onUserInput" ... />
  <ul v-if="results.length" class="absolute ...">
    <li v-for="u in results" @click="selectUser(u)">{{ u.fullName }}</li>
  </ul>
</div>
<p v-if="selectedUser">{{ selectedUser.fullName }}</p>
```

```vue
<!-- ✅ usar BaseAutocomplete -->
<BaseAutocomplete
  id="field-id"
  v-model="selectedUser"
  :label="t('User')"
  :search="searchUsers"
  option-label="fullName"
/>
```

**Cuando el v-model debe ser un IRI** (no el objeto completo), mantén un ref separado para el
autocomplete y extrae el IRI al construir el payload:
```js
const selectedUser = ref(null)

// en save():
const payload = { user: selectedUser.value?.["@id"] ?? null }
```

**Al limpiar o resetear**, simplemente asigna `null`:
```js
function resetForm() {
  selectedUser.value = null
}
```

Elimina además: los refs de texto (`userSearch`), los refs de sugerencias (`userSearchResults`),
los timers de debounce, y las funciones `onUserInput`, `selectUser`, `clearUser`.

---

### BaseColorPicker
```vue
<BaseColorPicker
  v-model="form.color"
  :label="t('Color')"
/>
```
**Props:** `modelValue` (Color instance from `colorjs.io`, required), `label` (no internal `t()` —
use `:label="t('...')"`), `error`.

**⚠ Important:** The model value must be a `Color` object from `colorjs.io`, never a plain string.
Three places in the script always need updating together:

```js
import Color from 'colorjs.io'

// 1. Initial state (ref declaration)
const form = ref({ color: new Color('#3B82F6') })

// 2. Loading existing data (e.g. openForm(item))
form.value.color = item ? new Color(item.color) : new Color('#3B82F6')

// 3. Serialising for the API (save payload)
const payload = {
  color: form.value.color.toString({ format: 'hex' }),
}
```

`colorjs.io` is already a project dependency — no installation needed.

---

### BaseDialog
```vue
<BaseDialog
  v-model:is-visible="myDialog"
  :title="editingItem ? t('Edit item') : t('Add item')"
  :style="{ width: '480px' }"
>
  <!-- form fields -->
  <template #footer>
    <BaseButton :label="t('Cancel')" icon="close" type="plain" @click="myDialog = false" />
    <BaseButton :label="t('Save')" icon="save" type="success" @click="save" />
  </template>
</BaseDialog>
```
**Props:** `title` (String, required), `headerIcon` (String, optional — MDI icon name).
**Model:** `isVisible` (Boolean) — bind with `v-model:is-visible`.
Always use `<BaseDialog>` instead of PrimeVue `<Dialog>` directly. It wraps Dialog with
`modal: true` and a consistent header layout.

- Extra attrs (e.g. `:style`, `:class`) fall through to the inner `<Dialog>` — use `:style="{ width: '...' }"` to control dialog width.
- Footer goes in the `#footer` named slot.
- Never import `Dialog` from `primevue/dialog` in a view — use `BaseDialog` instead.

---

### BaseTable
```vue
<BaseTable :values="rows" :is-loading="isLoading">
  <Column field="firstname" :header="t('First name')" sortable />
</BaseTable>
```
`BaseTable` (`assets/vue/components/basecomponents/BaseTable.vue`) wraps PrimeVue `DataTable`.
`Column` is globally registered in `main.js` — no import needed.

---

### BaseButton
```vue
<BaseButton :label="t('Save')" icon="save" type="success" @click="save" />
```
Always use `<BaseButton>` instead of a plain `<button>`. Import from
`../../components/basecomponents/BaseButton.vue`.

**CRUD color convention (`type` prop):**
- Create / add / save / import → `type="success"` (green)
- Read / view / export / list → `type="primary"` (blue)
- Update / edit / configure / move → `type="secondary"` (orange)
- Delete / disable / remove → `type="danger"` (red)
- Cancel / dismiss → `type="plain"` (gray)
- Buttons are for actions only — never style a non-action link as a button.

**Table row action convention:**
- Edit: `type="secondary-text"`, `icon="pencil"`, `only-icon`, `size="small"`
- Delete: `type="danger-text"`, `icon="delete"`, `only-icon`, `size="small"`
- Never put `ch-tool-icon` on icons inside a button — the icon inherits the button's text colour automatically.

---

### Fieldset (agrupación de campos)

Cuando un grupo de campos lleva un título visible, usa `<Fieldset>` de PrimeVue en lugar de
`<div class="field-group">` + `<label>`.

```vue
import Fieldset from "primevue/fieldset"

<Fieldset :legend="t('Tags')">
  <div class="flex flex-wrap gap-4">
    <BaseCheckbox ... />
  </div>
</Fieldset>
```

**Cuándo aplicarlo:** cualquier `<div>` con una `<label>` cuyo único propósito es titular un
grupo de inputs (checkboxes, radios, campos relacionados). No lo uses para un campo único —
cada `Base*` component ya renderiza su propio label.

---

### SectionHeader

Usar `<SectionHeader>` para encabezados de sección o página en lugar de un `<h2>` / `<div>` manual.

```vue
import SectionHeader from "../../components/layout/SectionHeader.vue"

<SectionHeader :title="t('Benefit tags')">
  <BaseButton
    :label="t('Add tag')"
    icon="plus"
    type="success"
    @click="openForm()"
  />
</SectionHeader>
```

**Props:** `title` (String, required), `size` (String, default `"2"` → renderiza `<h2>`).
El slot por defecto es para botones de acción (aparecen a la derecha del título).
Incluye automáticamente `StudentViewButton` cuando hay contexto de curso — no añadirlo manualmente.

---

### Notificaciones — useNotification

Nunca usar `useToast` de PrimeVue directamente. Usar siempre el composable
`assets/vue/composables/notification.js`:

```js
import { useNotification } from "../../composables/notification"

const { showSuccessNotification, showErrorNotification } = useNotification()

// éxito
showSuccessNotification(t("Saved"))

// error desde un objeto Error (Axios o JS nativo)
showErrorNotification(e)

// error con mensaje fijo (cuando el catch no tiene el mensaje correcto)
showErrorNotification(t("Cannot delete a benefit that has active assignments."))
```

`showErrorNotification` acepta tanto un objeto `Error` / respuesta Axios como un string.
Sanitiza el mensaje, filtra leakage de excepciones internas y evita toasts duplicados.

**Métodos disponibles:** `showSuccessNotification`, `showInfoNotification`,
`showWarningNotification`, `showErrorNotification`.

---

## Checklist

Work through the target file(s) in this order:

1. **Read** the file before editing.
2. For each native form element found:
   a. Identify the correct Base* component from the mapping above.
   b. Note the existing `v-model`, `name`, and any event handlers (`@input`, `@change`, etc.).
   c. Apply the label translation rule (raw key for BaseTextArea, `t()` for all others).
   d. Preserve `name` attributes — Behat tests target inputs by name.
   e. For `BaseCalendar` replacements, check initialization values and filter comparisons.
   f. For `BaseSelect` replacements, prepare an options computed if the source data uses
   non-standard or unsafe property names (like `@id`).
3. Add all required imports in alphabetical order alongside existing base component imports.
4. Remove any `<label>` elements that were paired with the replaced inputs.
5. Remove wrapper `<div>` elements that existed only to group the label+input pair, unless they
   carry layout classes needed by the surrounding flex/grid container.
6. Do **not** replace:
    - `<input type="checkbox">` used in v-model array bindings (multi-value selection).
    - `<input type="color">` unless the form already imports `colorjs.io` or it is trivial to add.
    - Inputs inside third-party component slots that require a native element.

---

## Final verification — cross-check template tags vs `<script setup>` imports

Before declaring the file done, run this check on EVERY Vue file you created or modified.
`yarn build` will compile successfully even when imports are missing, so this is the only
reliable gate.

```bash
file=assets/vue/views/path/to/YourFile.vue

# Components used in the template (Base*, PrimeVue PascalCase, layout):
used=$(grep -oE '<(Base[A-Z][A-Za-z0-9]+|SectionHeader|Fieldset|Column)\b' "$file" \
  | sed 's/^<//' | sort -u)

# Components imported in <script setup>:
imported=$(grep -oE '^import [A-Z][A-Za-z0-9]+ from' "$file" \
  | awk '{print $2}' | sort -u)

# Anything in `used` minus `imported` minus `Column` (globally registered) is a missing import:
echo "USED:"; echo "$used"
echo "IMPORTED:"; echo "$imported"
echo "MISSING:"; comm -23 <(echo "$used") <(printf '%s\nColumn\n' "$imported" | sort -u)
```

If `MISSING` is non-empty, **add the imports before returning**. Re-run `yarn build` after
adding them as a sanity check, but remember that build success alone does NOT prove the
imports are correct — only the diff above does.

**Common offenders observed in this project**: `BaseDialog`, `BaseSelect`, `BaseTable`,
`BaseAutocomplete`, `BaseInputNumber`, `SectionHeader`. These are easy to forget because
their tags read as if they were globally registered (`<BaseDialog>`, `<SectionHeader>`).
