<template>
  <div class="p-4">
    <SectionHeader :title="t('Professional functions')">
      <BaseButton
        :label="t('Add professional function')"
        icon="plus-box"
        type="success"
        @click="openCreate"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="isLoading"
      :values="items"
    >
      <Column
        :header="t('Title')"
        field="title"
        sortable
      />
      <Column
        :header="t('Parent function')"
        field="parentTitle"
      />
      <Column :header="t('Start date')">
        <template #body="{ data }">{{ data.startDate ? data.startDate.substring(0, 10) : "" }}</template>
      </Column>
      <Column :header="t('End date')">
        <template #body="{ data }">{{ data.endDate ? data.endDate.substring(0, 10) : "" }}</template>
      </Column>
      <Column
        :header="t('Actions')"
        style="width: 100px"
      >
        <template #body="{ data }">
          <div class="flex gap-1">
            <BaseButton
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEdit(data)"
            />
            <BaseButton
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="dialogVisible"
      :style="{ width: '520px' }"
      :title="editingItem ? t('Edit professional function') : t('Add professional function')"
    >
      <BaseInputText
        id="function-title"
        v-model="form.title"
        :label="t('Title')"
        name="function_title"
      />
      <BaseSelect
        id="function-parent"
        v-model="form.parent"
        :label="t('Parent function')"
        :options="parentOptions"
        allow-cleared
        name="function_parent"
      />
      <BaseTextArea
        id="function-description"
        v-model="form.description"
        label="Description"
        name="function_description"
        rows="3"
      />
      <BaseCalendar
        id="function-date-range"
        v-model="dateRange"
        :label="t('Period')"
        type="range"
      />
      <Fieldset
        v-if="editingItem"
        :legend="t('Activities')"
      >
        <div class="flex flex-wrap gap-2 mb-3">
          <span
            v-for="act in form.activities"
            :key="act"
            class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded flex items-center gap-1"
          >
            {{ activityLabel(act) }}
            <button
              class="hover:text-red-500"
              type="button"
              @click="removeActivity(act)"
            >
              ×
            </button>
          </span>
        </div>
        <BaseSelect
          id="function-add-activity"
          v-model="selectedActivityToAdd"
          :label="t('Add activity')"
          :options="activityOptions"
          allow-cleared
          name="function_add_activity"
        />
      </Fieldset>
      <template #footer>
        <BaseButton
          :disabled="isSaving"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="save"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Fieldset from "primevue/fieldset"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
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
const dateRange = ref(null)
const selectedActivityToAdd = ref(null)

const form = ref({ title: "", description: "", parent: null, activities: [] })

const parentOptions = computed(() =>
  items.value
    .filter((fn) => !editingItem.value || fn["@id"] !== editingItem.value["@id"])
    .map((fn) => ({ label: fn.title, value: fn["@id"] })),
)

const availableActivities = computed(() => allActivities.value.filter((a) => !form.value.activities.includes(a["@id"])))

const activityOptions = computed(() => availableActivities.value.map((a) => ({ label: a.title, value: a["@id"] })))

const activityLabel = (iri) => allActivities.value.find((a) => a["@id"] === iri)?.title ?? iri

watch(selectedActivityToAdd, (iri) => {
  if (iri) {
    if (!form.value.activities.includes(iri)) {
      form.value.activities.push(iri)
    }
    selectedActivityToAdd.value = null
  }
})

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
  form.value = { title: "", description: "", parent: null, activities: [] }
  dateRange.value = null
  dialogVisible.value = true
  await loadActivitiesIfNeeded()
}

async function openEdit(item) {
  editingItem.value = item
  dialogVisible.value = true
  const [fullRes] = await Promise.all([axios.get(`/api/professional_functions/${item.id}`), loadActivitiesIfNeeded()])
  const full = fullRes.data
  form.value = {
    title: full.title,
    description: full.description ?? "",
    parent: full.parent ? (full.parent["@id"] ?? full.parent) : null,
    activities: (full.activities ?? []).map((a) => (typeof a === "string" ? a : a["@id"])),
  }
  const startDate = full.startDate ? new Date(full.startDate.substring(0, 10)) : null
  const endDate = full.endDate ? new Date(full.endDate.substring(0, 10)) : null
  dateRange.value = startDate ? [startDate, endDate] : null
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
      startDate: dateRange.value?.[0] ? new Date(dateRange.value[0]).toISOString().slice(0, 10) : null,
      endDate: dateRange.value?.[1] ? new Date(dateRange.value[1]).toISOString().slice(0, 10) : null,
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
