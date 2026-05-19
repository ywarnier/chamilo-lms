<template>
  <div>
    <SectionHeader :title="t('Scheduled evaluations')">
      <BaseButton
        :label="t('Schedule evaluation')"
        icon="plus-box"
        type="success"
        @click="openForm(null)"
      />
    </SectionHeader>

    <!-- Search bar -->
    <div class="flex gap-4 items-end mb-4">
      <div class="flex-1 max-w-xs">
        <BaseAutocomplete
          id="evaluatee-filter"
          v-model="evaluateeFilter"
          :label="t('Search by evaluatee')"
          :search="searchUsers"
          option-label="fullName"
          @update:model-value="onFiltersChanged"
        />
      </div>
      <div>
        <BaseSelect
          id="status-filter"
          v-model="statusFilter"
          :label="t('Status')"
          :options="statusFilterOptions"
          allow-cleared
          name="status"
          @update:model-value="onFiltersChanged"
        />
      </div>
    </div>

    <BaseTable
      v-model:rows="pageSize"
      :is-loading="loading"
      :lazy="true"
      :total-items="total"
      :values="evaluations"
      data-key="id"
      @page="onPage"
    >
      <Column :header="t('Evaluatee')">
        <template #body="{ data }">
          {{ data.evaluatedUser?.fullName }}
        </template>
      </Column>
      <Column :header="t('Evaluator')">
        <template #body="{ data }">
          {{ data.evaluatorUser?.fullName }}
        </template>
      </Column>
      <Column :header="t('Template')">
        <template #body="{ data }">
          {{ data.template?.title }}
        </template>
      </Column>
      <Column :header="t('Stage')">
        <template #body="{ data }">
          {{ data.stage?.title }}
        </template>
      </Column>
      <Column :header="t('Scheduled date')">
        <template #body="{ data }">
          {{ formatDate(data.scheduledAt) }}
        </template>
      </Column>
      <Column :header="t('Status')">
        <template #body="{ data }">
          <span :class="statusBadge(data.status)">
            {{ t(statusLabel(data.status)) }}
          </span>
        </template>
      </Column>
      <Column :exportable="false">
        <template #body="{ data }">
          <div class="flex justify-end gap-2">
            <BaseButton
              :label="t('Open')"
              icon="eye-on"
              only-icon
              size="small"
              type="primary-text"
              @click="goToExecute(data)"
            />
            <BaseButton
              v-if="data.status === 'scheduled'"
              :label="t('Edit')"
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
              @click="openForm(data)"
            />
            <BaseButton
              v-if="data.status === 'scheduled'"
              :label="t('Delete')"
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

    <!-- Create / Edit dialog -->
    <BaseDialog
      v-if="showDialog"
      v-model:is-visible="showDialog"
      :title="form.id ? t('Edit evaluation') : t('Schedule evaluation')"
    >
      <form
        class="space-y-4"
        @submit.prevent="save"
      >
        <BaseAutocomplete
          id="form-evaluatedUser"
          v-model="form.evaluatedUser"
          :disabled="!!form.id"
          :label="t('Evaluatee')"
          :search="searchUsers"
          option-label="fullName"
        />
        <BaseAutocomplete
          id="form-evaluatorUsers"
          v-model="form.evaluatorUsers"
          :is-multiple="true"
          :label="t('Evaluator(s)')"
          :search="searchUsers"
          option-label="fullName"
        />
        <BaseSelect
          id="form-template"
          v-model="form.template"
          :disabled="!!form.id"
          :hast-empty-value="true"
          :label="t('Template')"
          :options="templateOptions"
          name="template"
        />
        <BaseSelect
          id="form-stage"
          v-model="form.stage"
          :hast-empty-value="true"
          :label="t('Evaluation stage')"
          :options="stageOptions"
          name="stage"
        />
        <BaseInputText
          id="evaluation-date"
          v-model="form.scheduledAt"
          :label="t('Scheduled date')"
          name="scheduledAt"
          required
          type="datetime-local"
        />
      </form>
      <template #footer>
        <BaseButton
          :label="t('Save')"
          :loading="saving"
          icon="content-save"
          type="success"
          @click="save"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import baseService from "../../services/baseService"
import axios from "axios"

const { t } = useI18n()
const router = useRouter()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const evaluations = ref([])
const templates = ref([])
const stages = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const evaluateeFilter = ref(null)
const statusFilter = ref("")

const statusFilterOptions = computed(() => [
  { label: t("Scheduled"), value: "scheduled" },
  { label: t("Done"), value: "done" },
  { label: t("Feedbacked"), value: "feedbacked" },
  { label: t("Closed"), value: "closed" },
])

const templateOptions = computed(() => templates.value.map((tpl) => ({ label: tpl.title, value: tpl["@id"] })))
const stageOptions = computed(() => stages.value.map((s) => ({ label: s.title, value: s["@id"] })))

const emptyForm = () => ({
  id: null,
  evaluatedUser: null,
  evaluatorUsers: [],
  template: "",
  stage: "",
  scheduledAt: "",
})
const form = ref(emptyForm())

async function searchUsers(query) {
  const result = await baseService.getCollection("/api/users", { search: query, itemsPerPage: 10 })

  return result.items
}

function formatDate(dateStr) {
  if (!dateStr) return ""
  return new Date(dateStr).toLocaleDateString()
}

function statusLabel(status) {
  const map = { scheduled: "Scheduled", done: "Done", feedbacked: "Feedbacked", closed: "Closed" }
  return map[status] ?? status
}

function statusBadge(status) {
  const map = {
    scheduled: "bg-blue-100 text-blue-700",
    done: "bg-yellow-100 text-yellow-700",
    feedbacked: "bg-purple-100 text-purple-700",
    closed: "bg-gray-100 text-gray-700",
  }
  return (map[status] ?? "bg-gray-100 text-gray-700") + " text-xs px-2 py-0.5 rounded-full font-medium"
}

async function load() {
  loading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: pageSize.value,
    }
    if (evaluateeFilter.value?.["@id"]) {
      params.evaluatedUser = evaluateeFilter.value["@id"]
    }
    if (statusFilter.value) {
      params.status = statusFilter.value
    }
    const { items, totalItems } = await baseService.getCollection("/api/performance_appraisals", params)
    evaluations.value = items
    total.value = totalItems
  } catch {
    showErrorNotification(t("Could not load evaluations"))
  } finally {
    loading.value = false
  }
}

function onPage(event) {
  page.value = event.page + 1
  pageSize.value = event.rows
  load()
}

function onFiltersChanged() {
  page.value = 1
  load()
}

async function loadReferenceData() {
  const [tRes, sRes] = await Promise.all([
    baseService.getCollection("/api/performance_appraisal_templates", { pagination: false }),
    baseService.getCollection("/api/recruitment_stages", { pagination: false }),
  ])
  templates.value = tRes.items
  stages.value = sRes.items
}

function openForm(item) {
  if (item) {
    form.value = {
      id: item.id,
      evaluatedUser: item.evaluatedUser ?? null,
      evaluatorUsers: item.evaluatorUser ? [item.evaluatorUser] : [],
      template: item.template?.["@id"] ?? "",
      stage: item.stage?.["@id"] ?? "",
      scheduledAt: item.scheduledAt ? item.scheduledAt.replace("Z", "").slice(0, 16) : "",
    }
  } else {
    form.value = emptyForm()
  }
  showDialog.value = true
}

function goToExecute(item) {
  router.push({ name: "HrEvaluationExecute", params: { id: item.id } })
}

async function save() {
  saving.value = true
  try {
    if (form.value.id) {
      // Edit: only stage and scheduledAt can change
      await baseService.put(`/api/performance_appraisals/${form.value.id}`, {
        stage: form.value.stage,
        scheduledAt: form.value.scheduledAt ? new Date(form.value.scheduledAt).toISOString() : null,
      })
      showSuccessNotification(t("Evaluation updated"))
    } else {
      // Create one appraisal per evaluator and schedule reminders
      const evaluatorIris = (form.value.evaluatorUsers ?? []).map((u) => u["@id"]).filter(Boolean)
      for (const evaluatorIri of evaluatorIris) {
        const created = await baseService.post("/api/performance_appraisals", {
          evaluatedUser: form.value.evaluatedUser?.["@id"] ?? null,
          evaluatorUser: evaluatorIri,
          template: form.value.template,
          stage: form.value.stage,
          scheduledAt: form.value.scheduledAt ? new Date(form.value.scheduledAt).toISOString() : null,
        })
        const appraisalId = created.id ?? created["@id"]?.split("/").pop()
        if (appraisalId) {
          await axios.post(`/hr/evaluations/${appraisalId}/schedule-reminders`)
        }
      }
      showSuccessNotification(t("Evaluation scheduled"))
    }
    showDialog.value = false
    await load()
  } catch {
    showErrorNotification(t("Could not save evaluation"))
  } finally {
    saving.value = false
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this evaluation?"),
    accept: () => doDelete(item),
  })
}

async function doDelete(item) {
  try {
    await baseService.delete(`/api/performance_appraisals/${item.id}`)
    showSuccessNotification(t("Deleted"))
    await load()
  } catch {
    showErrorNotification(t("Could not delete"))
  }
}

onMounted(async () => {
  await Promise.all([load(), loadReferenceData()])
})
</script>
