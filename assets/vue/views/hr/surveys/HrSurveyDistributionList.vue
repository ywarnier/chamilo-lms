<template>
  <div>
    <!-- ── Survey management ───────────────────────────── -->
    <SectionHeader :title="pageTitle">
      <BaseButton
        :label="t('New survey')"
        icon="mdi-plus-box"
        type="success"
        @click="openNewSurveyLegacy()"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="surveysLoading"
      :values="surveys"
      data-key="iid"
    >
      <Column
        field="title"
        :header="t('Title')"
      >
        <template #body="{ data }">{{ stripHtml(data.title) }}</template>
      </Column>
      <Column
        field="availFrom"
        :header="t('Available from')"
      >
        <template #body="{ data }">{{ formatDate(data.availFrom) }}</template>
      </Column>
      <Column
        field="availTill"
        :header="t('Available until')"
      >
        <template #body="{ data }">{{ formatDate(data.availTill) }}</template>
      </Column>
      <Column
        :header="t('Actions')"
        :exportable="false"
      >
        <template #body="{ data }">
          <div class="flex gap-2">
            <BaseButton
              only-icon
              size="small"
              type="secondary-text"
              icon="pencil"
              :label="t('Edit')"
              @click="openSurveyDialog(data)"
            />
            <BaseButton
              only-icon
              size="small"
              type="primary-text"
              icon="mdi-format-list-numbered"
              :label="t('Edit questions')"
              @click="openSurveyQuestions(data)"
            />
            <BaseButton
              only-icon
              size="small"
              type="danger-text"
              icon="delete"
              :label="t('Delete')"
              @click="confirmDeleteSurvey(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <!-- ── Distributions ──────────────────────────────── -->
    <SectionHeader :title="t('Distributions')">
      <BaseButton
        :label="t('Add distribution')"
        icon="mdi-plus-box"
        type="success"
        @click="openDistributionDialog()"
      />
    </SectionHeader>

    <BaseTable
      v-model:rows="pageSize"
      :is-loading="isLoading"
      :lazy="true"
      :total-items="total"
      :values="distributions"
      data-key="id"
      @page="onPage"
      @sort="onSort"
    >
      <Column
        field="surveyTitle"
        :header="t('Survey')"
        sortable
      />
      <Column
        field="businessUnitTitle"
        :header="t('Unit')"
        sortable
      />
      <Column
        field="createdAt"
        :header="t('Date')"
        sortable
      >
        <template #body="{ data }">{{ formatDate(data.createdAt) }}</template>
      </Column>
      <Column
        :header="t('Actions')"
        :exportable="false"
      >
        <template #body="{ data }">
          <div class="flex gap-2">
            <BaseButton
              only-icon
              size="small"
              icon="mdi-chart-bar"
              type="primary-text"
              :label="t('Results')"
              @click="openResults(data)"
            />
            <BaseButton
              only-icon
              size="small"
              icon="mdi-delete"
              type="danger-text"
              :label="t('Delete')"
              @click="confirmDeleteDistribution(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <!-- ── Survey create / edit dialog ───────────────── -->
    <BaseDialog
      v-model:is-visible="surveyDialog"
      :title="editingSurvey ? t('Edit survey') : t('New survey')"
      :style="{ width: '480px' }"
    >
      <div class="flex flex-col gap-4">
        <BaseInputText
          id="surveyTitle"
          name="title"
          v-model="surveyForm.title"
          :label="t('Title')"
          :required="true"
        />
        <BaseInputText
          id="surveySubtitle"
          name="subtitle"
          v-model="surveyForm.subtitle"
          :label="t('Subtitle')"
        />
        <BaseTextArea
          id="surveyIntro"
          name="intro"
          v-model="surveyForm.intro"
          :label="t('Introduction')"
          :rows="3"
        />
        <div class="flex gap-4">
          <BaseCalendar
            id="surveyAvailFrom"
            name="avail_from"
            v-model="surveyForm.availFrom"
            :label="t('Available from')"
            show-time
          />
          <BaseCalendar
            id="surveyAvailTill"
            name="avail_till"
            v-model="surveyForm.availTill"
            :label="t('Available until')"
            show-time
          />
        </div>
        <BaseSelect
          id="surveyAnonymous"
          name="anonymous"
          v-model="surveyForm.anonymous"
          :label="t('Anonymous responses')"
          :options="anonymousOptions"
          option-label="label"
          option-value="value"
        />
      </div>
      <template #footer>
        <BaseButton
          :disabled="!surveyForm.title"
          :label="t('Save')"
          :loading="isSavingSurvey"
          icon="save"
          type="success"
          @click="saveSurvey"
        />
        <BaseButton
          v-if="editingSurvey"
          :label="t('Advanced settings')"
          icon="mdi-hammer-wrench"
          type="secondary"
          @click="openAdvancedSettings()"
        />
        <BaseButton
          :label="t('Cancel')"
          type="plain"
          @click="surveyDialog = false"
        />
      </template>
    </BaseDialog>

    <!-- ── Distribution create dialog ────────────────── -->
    <BaseDialog
      v-model:is-visible="distributionDialog"
      :title="t('Add distribution')"
      :style="{ width: '480px' }"
    >
      <div class="flex flex-col gap-4">
        <BaseSelect
          id="surveySelect"
          name="survey"
          v-model="distributionForm.surveyIri"
          :label="t('Survey')"
          :options="surveyOptions"
          option-label="label"
          option-value="value"
          :loading="surveysLoading"
          :placeholder="t('Select a survey')"
        />
        <BaseSelect
          id="unitSelect"
          name="business_unit"
          v-model="distributionForm.businessUnitIri"
          :label="t('Unit')"
          :options="unitOptions"
          option-label="label"
          option-value="value"
          :loading="unitsLoading"
          :placeholder="t('Select a unit')"
        />
      </div>
      <template #footer>
        <BaseButton
          :disabled="!distributionForm.surveyIri || !distributionForm.businessUnitIri"
          :label="t('Save')"
          :loading="isSaving"
          icon="save"
          type="success"
          @click="saveDistribution"
        />
        <BaseButton
          :label="t('Cancel')"
          type="plain"
          @click="distributionDialog = false"
        />
      </template>
    </BaseDialog>

    <!-- ── Results dialog ─────────────────────────────── -->
    <BaseDialog
      v-model:is-visible="resultsDialog"
      :close-label="t('Close')"
      :title="t('Results')"
      :style="{ width: '720px' }"
    >
      <div
        v-if="resultsLoading"
        class="py-6 text-center text-gray-500"
      >
        {{ t("Loading") }}
      </div>
      <div
        v-else-if="results"
        class="flex flex-col gap-4"
      >
        <div class="flex justify-between rounded bg-gray-100 px-4 py-2 text-sm">
          <span>{{ results.surveyTitle }}</span>
          <span>
            {{ t("Total invited") }}: <strong>{{ results.totalInvited }}</strong>
            &nbsp;·&nbsp;
            {{ t("Total answered") }}: <strong>{{ results.totalAnswered }}</strong>
          </span>
        </div>
        <div
          v-if="!results.questions.length"
          class="py-6 text-center text-gray-500"
        >
          {{ t("No responses yet") }}
        </div>
        <div
          v-for="question in results.questions"
          :key="question.questionId"
          class="rounded border border-gray-200 p-3"
        >
          <div class="mb-2 font-semibold">{{ question.title }}</div>
          <div class="mb-2 text-xs text-gray-500">
            {{ t("Type") }}: {{ question.type }} · {{ t("Total responses") }}:
            {{ question.totalResponses }}
          </div>
          <div
            v-if="!question.responses.length"
            class="text-sm text-gray-500"
          >
            {{ t("No responses yet") }}
          </div>
          <ul
            v-else
            class="flex flex-col gap-1 text-sm"
          >
            <li
              v-for="(resp, idx) in question.responses"
              :key="idx"
              class="flex justify-between border-b border-gray-100 py-1"
            >
              <span class="truncate">{{ optionLabel(question, resp.optionId) }}</span>
              <span class="font-mono">{{ resp.count }}</span>
            </li>
          </ul>
        </div>
      </div>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useConfirmation } from "../../../composables/useConfirmation"
import { useNotification } from "../../../composables/notification"
import baseService from "../../../services/baseService"
import SectionHeader from "../../../components/layout/SectionHeader.vue"
import BaseButton from "../../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../../components/basecomponents/BaseTable.vue"
import BaseDialog from "../../../components/basecomponents/BaseDialog.vue"
import BaseSelect from "../../../components/basecomponents/BaseSelect.vue"
import BaseInputText from "../../../components/basecomponents/BaseInputText.vue"
import BaseTextArea from "../../../components/basecomponents/BaseTextArea.vue"
import BaseCalendar from "../../../components/basecomponents/BaseCalendar.vue"

const props = defineProps({
  category: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
})

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

// ── Distributions state ────────────────────────────────────────
const isLoading = ref(false)
const isSaving = ref(false)
const distributionDialog = ref(false)
const distributions = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const sortField = ref(null)
const sortOrder = ref(null)
const unitOptions = ref([])
const unitsLoading = ref(false)
const resultsDialog = ref(false)
const resultsLoading = ref(false)
const results = ref(null)

const distributionForm = ref({ surveyIri: null, businessUnitIri: null })

// ── Survey management state ────────────────────────────────────
const surveysLoading = ref(false)
const isSavingSurvey = ref(false)
const surveyDialog = ref(false)
const surveys = ref([])
const editingSurvey = ref(null)

const emptyForm = () => ({ title: "", subtitle: "", intro: "", availFrom: null, availTill: null, anonymous: "0" })
const surveyForm = ref(emptyForm())

const surveyOptions = computed(() =>
  surveys.value.map((s) => ({ label: s.title, value: s["@id"] ?? `/api/c_surveys/${s.iid}` })),
)

const pageTitle = computed(() => props.title)

const anonymousOptions = [
  { label: t("No"), value: "0" },
  { label: t("Yes"), value: "Y" },
]

const sortFieldMap = {
  surveyTitle: "survey.title",
  businessUnitTitle: "businessUnit.title",
  createdAt: "createdAt",
}

function formatDate(dateStr) {
  if (!dateStr) return ""
  return new Date(dateStr).toLocaleString([], { dateStyle: "short", timeStyle: "short" })
}

function stripHtml(html) {
  if (!html) return ""
  return html.replace(/<[^>]+>/g, "")
}

function utcStringToLocalDate(str) {
  if (!str) return null
  return new Date(str)
}

function localDateToUtcIso(date) {
  if (!date) return null
  return date.toISOString()
}

// ── Survey CRUD ────────────────────────────────────────────────

function openNewSurveyLegacy() {
  window.location.href = `/main/survey/create_new_survey.php?action=add&hr_mode=1&hr_category=${props.category}`
}

function openSurveyQuestions(survey) {
  window.location.href = `/main/survey/survey.php?survey_id=${survey.iid}&hr_mode=1&hr_category=${props.category}`
}

function openAdvancedSettings() {
  if (!editingSurvey.value) return
  window.location.href = `/main/survey/create_new_survey.php?action=edit&survey_id=${editingSurvey.value.iid}&hr_mode=1&hr_category=${props.category}`
}

async function loadSurveys() {
  surveysLoading.value = true
  try {
    const { items } = await baseService.getCollection("/api/c_surveys", { hrCategory: props.category })
    surveys.value = items
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    surveysLoading.value = false
  }
}

function openSurveyDialog(survey = null) {
  editingSurvey.value = survey
  surveyForm.value = survey
    ? {
        title: survey.title ?? "",
        subtitle: survey.subtitle ?? "",
        intro: survey.intro ?? "",
        availFrom: utcStringToLocalDate(survey.availFrom),
        availTill: utcStringToLocalDate(survey.availTill),
        anonymous: survey.anonymous ?? "0",
      }
    : emptyForm()
  surveyDialog.value = true
}

async function saveSurvey() {
  isSavingSurvey.value = true
  try {
    const payload = {
      title: surveyForm.value.title,
      subtitle: surveyForm.value.subtitle || "",
      intro: surveyForm.value.intro || "",
      availFrom: localDateToUtcIso(surveyForm.value.availFrom),
      availTill: localDateToUtcIso(surveyForm.value.availTill),
      anonymous: surveyForm.value.anonymous,
      hrCategory: props.category,
    }

    if (editingSurvey.value) {
      const iri = editingSurvey.value["@id"] ?? `/api/c_surveys/${editingSurvey.value.iid}`
      await baseService.put(iri, payload)
      showSuccessNotification(t("Survey updated"))
    } else {
      // Auto-generate code: server processor also generates one as fallback,
      // but sending it avoids the @Assert\NotBlank validation error.
      payload.code = "HR" + Date.now().toString(36).toUpperCase().slice(-12)
      await baseService.post("/api/c_surveys", payload)
      showSuccessNotification(t("Survey created"))
    }

    surveyDialog.value = false
    await loadSurveys()
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isSavingSurvey.value = false
  }
}

function confirmDeleteSurvey(survey) {
  requireConfirmation({
    message: t("Are you sure you want to delete this survey? All related distributions will also be removed."),
    accept: () => deleteSurvey(survey),
  })
}

async function deleteSurvey(survey) {
  try {
    const iri = survey["@id"] ?? `/api/c_surveys/${survey.iid}`
    await baseService.delete(iri)
    showSuccessNotification(t("Survey deleted"))
    await loadSurveys()
  } catch {
    showErrorNotification(t("An error occurred"))
  }
}

// ── Distribution CRUD ──────────────────────────────────────────

async function load() {
  isLoading.value = true
  try {
    const params = {
      category: props.category,
      page: page.value,
      itemsPerPage: pageSize.value,
    }
    if (sortField.value) {
      const apiField = sortFieldMap[sortField.value] ?? sortField.value
      params[`order[${apiField}]`] = sortOrder.value === 1 ? "asc" : "desc"
    }
    const { items, totalItems } = await baseService.getCollection("/api/hr_survey_distributions", params)
    distributions.value = items
    total.value = totalItems
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isLoading.value = false
  }
}

function onPage(event) {
  page.value = event.page + 1
  pageSize.value = event.rows
  load()
}

function onSort(event) {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  page.value = 1
  load()
}

async function loadUnits() {
  unitsLoading.value = true
  try {
    const response = await baseService.get("/api/business_units", { pagination: false })
    const units = response["hydra:member"] ?? response
    unitOptions.value = units.map((u) => ({ label: u.title, value: u["@id"] }))
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    unitsLoading.value = false
  }
}

function openDistributionDialog() {
  distributionForm.value = { surveyIri: null, businessUnitIri: null }
  distributionDialog.value = true
}

async function saveDistribution() {
  isSaving.value = true
  try {
    await baseService.post("/api/hr_survey_distributions", {
      survey: distributionForm.value.surveyIri,
      businessUnit: distributionForm.value.businessUnitIri,
      category: props.category,
    })
    distributionDialog.value = false
    showSuccessNotification(t("Distribution added"))
    await load()
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isSaving.value = false
  }
}

function confirmDeleteDistribution(distribution) {
  requireConfirmation({
    message: t("Are you sure you want to remove this distribution?"),
    accept: () => deleteDistribution(distribution),
  })
}

async function deleteDistribution(distribution) {
  try {
    await baseService.delete(distribution["@id"])
    showSuccessNotification(t("Distribution removed"))
    await load()
  } catch {
    showErrorNotification(t("An error occurred"))
  }
}

async function openResults(distribution) {
  results.value = null
  resultsDialog.value = true
  resultsLoading.value = true
  try {
    const id = distribution["@id"].split("/").pop()
    results.value = await baseService.get(`/api/hr_survey_distributions/${id}/results`)
  } catch {
    showErrorNotification(t("An error occurred"))
    resultsDialog.value = false
  } finally {
    resultsLoading.value = false
  }
}

function optionLabel(question, optionId) {
  const match = question.options.find((o) => String(o.optionId) === String(optionId))
  return match ? match.text : optionId
}

onMounted(async () => {
  await Promise.all([loadSurveys(), load(), loadUnits()])
})
</script>
