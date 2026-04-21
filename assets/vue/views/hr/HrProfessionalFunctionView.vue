<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ t("Professional functions") }}</h1>
      <BaseButton
        type="success"
        icon="plus-box"
        :label="t('Add professional function')"
        @click="openCreate"
      />
    </div>

    <BaseTable :values="items" :is-loading="isLoading">
      <Column field="title" :header="t('Title')" sortable />
      <Column field="parentTitle" :header="t('Parent function')" />
      <Column :header="t('Start date')">
        <template #body="{ data }">{{ data.startDate ? data.startDate.substring(0, 10) : "" }}</template>
      </Column>
      <Column :header="t('End date')">
        <template #body="{ data }">{{ data.endDate ? data.endDate.substring(0, 10) : "" }}</template>
      </Column>
      <Column :header="t('Actions')" style="width: 100px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <BaseButton
              type="secondary-text"
              icon="pencil"
              only-icon
              size="small"
              @click="openEdit(data)"
            />
            <BaseButton
              type="danger-text"
              icon="delete"
              only-icon
              size="small"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <Dialog
      v-model:visible="dialogVisible"
      :header="editingItem ? t('Edit professional function') : t('Add professional function')"
      modal
      :style="{ width: '520px' }"
    >
      <div class="flex flex-col gap-3 pt-2">
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Title") }} *</label>
          <input
            v-model="form.title"
            name="function_title"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Parent function") }}</label>
          <select
            v-model="form.parent"
            name="function_parent"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option :value="null">— {{ t("None") }} —</option>
            <option v-for="fn in parentOptions" :key="fn['@id']" :value="fn['@id']">
              {{ fn.title }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Description") }}</label>
          <textarea
            v-model="form.description"
            name="function_description"
            rows="3"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          />
        </div>
        <div class="flex gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1">{{ t("Start date") }}</label>
            <input
              v-model="form.startDate"
              name="function_start_date"
              type="date"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            />
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1">{{ t("End date") }}</label>
            <input
              v-model="form.endDate"
              name="function_end_date"
              type="date"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            />
          </div>
        </div>
        <div v-if="editingItem">
          <label class="block text-sm font-medium mb-1">{{ t("Activities") }}</label>
          <div class="flex flex-wrap gap-2 mb-2">
            <span
              v-for="act in form.activities"
              :key="act"
              class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded flex items-center gap-1"
            >
              {{ activityLabel(act) }}
              <button type="button" class="hover:text-red-500" @click="removeActivity(act)">×</button>
            </span>
          </div>
          <select
            name="function_add_activity"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            @change="addActivity($event)"
          >
            <option value="">{{ t("Add activity…") }}</option>
            <option
              v-for="act in availableActivities"
              :key="act['@id']"
              :value="act['@id']"
            >
              {{ act.title }}
            </option>
          </select>
        </div>
      </div>
      <template #footer>
        <div class="flex gap-2 justify-end">
          <BaseButton type="plain" :label="t('Cancel')" @click="dialogVisible = false" />
          <BaseButton type="success" :label="t('Save')" :disabled="isSaving" @click="save" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import Dialog from "primevue/dialog"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import axios from "axios"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()

const items = ref([])
const allActivities = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const dialogVisible = ref(false)
const editingItem = ref(null)

const form = ref({ title: "", description: "", parent: null, startDate: "", endDate: "", activities: [] })

const parentOptions = computed(() =>
  items.value.filter((fn) => !editingItem.value || fn["@id"] !== editingItem.value["@id"]),
)

const availableActivities = computed(() =>
  allActivities.value.filter((a) => !form.value.activities.includes(a["@id"])),
)

const activityLabel = (iri) => allActivities.value.find((a) => a["@id"] === iri)?.title ?? iri

async function load() {
  isLoading.value = true
  try {
    const res = await axios.get("/hr/professional-functions-data")
    items.value = res.data
  } finally {
    isLoading.value = false
  }
}

async function loadActivitiesIfNeeded() {
  if (allActivities.value.length > 0) return
  const res = await axios.get("/api/activities?pagination=false")
  allActivities.value = res.data["hydra:member"] ?? []
}

async function openCreate() {
  editingItem.value = null
  form.value = { title: "", description: "", parent: null, startDate: "", endDate: "", activities: [] }
  dialogVisible.value = true
  await loadActivitiesIfNeeded()
}

async function openEdit(item) {
  editingItem.value = item
  dialogVisible.value = true
  const [fullRes] = await Promise.all([
    axios.get(`/api/professional_functions/${item.id}`),
    loadActivitiesIfNeeded(),
  ])
  const full = fullRes.data
  form.value = {
    title: full.title,
    description: full.description ?? "",
    parent: full.parent ? (full.parent["@id"] ?? full.parent) : null,
    startDate: full.startDate ? full.startDate.substring(0, 10) : "",
    endDate: full.endDate ? full.endDate.substring(0, 10) : "",
    activities: (full.activities ?? []).map((a) => (typeof a === "string" ? a : a["@id"])),
  }
}

function addActivity(event) {
  const iri = event.target.value
  if (iri && !form.value.activities.includes(iri)) {
    form.value.activities.push(iri)
  }
  event.target.value = ""
}

function removeActivity(iri) {
  form.value.activities = form.value.activities.filter((a) => a !== iri)
}

async function save() {
  if (!form.value.title.trim()) return
  isSaving.value = true
  try {
    const payload = {
      title: form.value.title,
      description: form.value.description || null,
      parent: form.value.parent || null,
      startDate: form.value.startDate || null,
      endDate: form.value.endDate || null,
      activities: form.value.activities,
    }
    if (editingItem.value) {
      await axios.put(editingItem.value["@id"], payload)
    } else {
      await axios.post("/api/professional_functions", payload)
    }
    dialogVisible.value = false
    await load()
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      await axios.delete(item["@id"])
      await load()
    },
  })
}

onMounted(load)
</script>
