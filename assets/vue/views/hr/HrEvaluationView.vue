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
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Search by evaluatee") }}</label>
        <input
          v-model="search"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          name="search"
          :placeholder="t('Name...')"
          type="text"
          @input="debouncedLoad"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Status") }}</label>
        <select
          v-model="statusFilter"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
          name="status"
          @change="load"
        >
          <option value="">
            {{ t("All") }}
          </option>
          <option value="scheduled">
            {{ t("Scheduled") }}
          </option>
          <option value="done">
            {{ t("Done") }}
          </option>
          <option value="feedbacked">
            {{ t("Feedbacked") }}
          </option>
          <option value="closed">
            {{ t("Closed") }}
          </option>
        </select>
      </div>
    </div>

    <BaseTable
      :is-loading="loading"
      :values="filteredEvaluations"
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
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Evaluatee") }}</label>
          <select
            v-model="form.evaluatedUser"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            name="evaluatedUser"
            required
          >
            <option value="">
              — {{ t("Select") }} —
            </option>
            <option
              v-for="u in users"
              :key="u['@id']"
              :value="u['@id']"
            >
              {{ u.fullName }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Evaluator(s)") }}</label>
          <select
            v-model="form.evaluatorUsers"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            multiple
            name="evaluatorUsers"
            required
            size="5"
          >
            <option
              v-for="u in users"
              :key="u['@id']"
              :value="u['@id']"
            >
              {{ u.fullName }}
            </option>
          </select>
          <p class="text-xs text-gray-400 mt-1">
            {{ t("Hold Ctrl/Cmd to select multiple evaluators.") }}
          </p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Template") }}</label>
          <select
            v-model="form.template"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            :disabled="!!form.id"
            name="template"
            required
          >
            <option value="">
              — {{ t("Select") }} —
            </option>
            <option
              v-for="tpl in templates"
              :key="tpl['@id']"
              :value="tpl['@id']"
            >
              {{ tpl.title }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Evaluation stage") }}</label>
          <select
            v-model="form.stage"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            name="stage"
            required
          >
            <option value="">
              — {{ t("Select") }} —
            </option>
            <option
              v-for="s in stages"
              :key="s['@id']"
              :value="s['@id']"
            >
              {{ s.title }}
            </option>
          </select>
        </div>
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
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
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
const users = ref([])
const templates = ref([])
const stages = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const search = ref("")
const statusFilter = ref("")
let debounceTimer = null

const emptyForm = () => ({
  id: null,
  evaluatedUser: "",
  evaluatorUsers: [],
  template: "",
  stage: "",
  scheduledAt: "",
})
const form = ref(emptyForm())

const filteredEvaluations = computed(() => {
  let list = evaluations.value
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter((e) => e.evaluatedUser?.fullName?.toLowerCase().includes(q))
  }
  if (statusFilter.value) {
    list = list.filter((e) => e.status === statusFilter.value)
  }
  return list
})

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

function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(load, 300)
}

async function load() {
  loading.value = true
  try {
    const res = await baseService.get("/api/performance_appraisals")
    evaluations.value = res["hydra:member"] ?? res
  } catch {
    showErrorNotification(t("Could not load evaluations"))
  } finally {
    loading.value = false
  }
}

async function loadReferenceData() {
  const [uRes, tRes, sRes] = await Promise.all([
    baseService.get("/api/users?properties[]=id&properties[]=fullName"),
    baseService.get("/api/performance_appraisal_templates"),
    baseService.get("/api/recruitment_stages"),
  ])
  users.value = uRes["hydra:member"] ?? uRes
  templates.value = tRes["hydra:member"] ?? tRes
  stages.value = sRes["hydra:member"] ?? sRes
}

function openForm(item) {
  if (item) {
    form.value = {
      id: item.id,
      evaluatedUser: item.evaluatedUser?.["@id"] ?? "",
      evaluatorUsers: [item.evaluatorUser?.["@id"] ?? ""],
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
      const evaluatorIris = Array.isArray(form.value.evaluatorUsers) ? form.value.evaluatorUsers : [form.value.evaluatorUsers]
      for (const evaluatorIri of evaluatorIris) {
        const created = await baseService.post("/api/performance_appraisals", {
          evaluatedUser: form.value.evaluatedUser,
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
