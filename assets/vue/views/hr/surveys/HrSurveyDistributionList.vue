<template>
  <div>
    <SectionHeader :title="pageTitle">
      <BaseButton
        :label="t('Add distribution')"
        icon="mdi-plus-box"
        type="success"
        @click="openDialog()"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="isLoading"
      :values="distributions"
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
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="dialog"
      :title="t('Add distribution')"
      :style="{ width: '480px' }"
    >
      <div class="flex flex-col gap-4">
        <BaseSelect
          id="surveySelect"
          name="survey"
          v-model="form.surveyIri"
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
          v-model="form.businessUnitIri"
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
          :disabled="!form.surveyIri || !form.businessUnitIri"
          :label="t('Save')"
          :loading="isSaving"
          icon="save"
          @click="save"
        />
      </template>
    </BaseDialog>

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

const isLoading = ref(false)
const isSaving = ref(false)
const dialog = ref(false)
const distributions = ref([])
const surveyOptions = ref([])
const unitOptions = ref([])
const surveysLoading = ref(false)
const unitsLoading = ref(false)
const resultsDialog = ref(false)
const resultsLoading = ref(false)
const results = ref(null)

const pageTitle = computed(() => props.title)

const form = ref({ surveyIri: null, businessUnitIri: null })

function formatDate(dateStr) {
  if (!dateStr) return ""
  return new Date(dateStr).toLocaleDateString()
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

async function load() {
  isLoading.value = true
  try {
    const response = await baseService.get("/api/hr_survey_distributions", { category: props.category })
    distributions.value = response["hydra:member"] ?? response
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isLoading.value = false
  }
}

async function loadSurveys() {
  surveysLoading.value = true
  try {
    const response = await baseService.get("/api/c_surveys", { pagination: false })
    const surveys = response["hydra:member"] ?? response
    surveyOptions.value = surveys.map((s) => ({ label: s.title, value: `/api/c_surveys/${s.iid}` }))
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    surveysLoading.value = false
  }
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

function openDialog() {
  form.value = { surveyIri: null, businessUnitIri: null }
  dialog.value = true
}

async function save() {
  isSaving.value = true
  try {
    await baseService.post("/api/hr_survey_distributions", {
      survey: form.value.surveyIri,
      businessUnit: form.value.businessUnitIri,
      category: props.category,
    })
    dialog.value = false
    showSuccessNotification(t("Distribution added"))
    await load()
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(distribution) {
  requireConfirmation({
    message: t("Are you sure you want to remove this distribution?"),
    accept: () => deleteDistribution(distribution),
  })
}

async function deleteDistribution(distribution) {
  try {
    await baseService.delete(distribution["@id"])
    showSuccessNotification(t("Distribution removed"))
    distributions.value = distributions.value.filter((d) => d["@id"] !== distribution["@id"])
  } catch {
    showErrorNotification(t("An error occurred"))
  }
}

onMounted(async () => {
  await Promise.all([load(), loadSurveys(), loadUnits()])
})
</script>
