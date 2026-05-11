<template>
  <div>
    <SectionHeader :title="t('ROI by course')" />

    <div class="mb-4 flex flex-wrap items-end gap-4">
      <div class="flex flex-col gap-1">
        <BaseCalendar
          id="dateRange"
          v-model="dateRange"
          :label="t('Date range')"
          type="range"
        />
      </div>
      <BaseButton
        :label="t('Filter')"
        icon="mdi-magnify"
        :disabled="!Array.isArray(dateRange) || dateRange.length < 2 || !dateRange[1]"
        @click="load"
      />
    </div>

    <BaseTable
      :is-loading="isLoading"
      :values="items"
    >
      <Column
        field="title"
        :header="t('Session')"
        sortable
      />
      <Column
        field="accessStartDate"
        :header="t('Start date')"
        sortable
      />
      <Column
        field="accessEndDate"
        :header="t('End date')"
        sortable
      />
      <Column
        field="cost"
        :header="t('Total cost')"
        sortable
      >
        <template #body="{ data }">{{ formatCurrency(data.cost) }}</template>
      </Column>
      <Column
        field="learnerCount"
        :header="t('Number of learners')"
        sortable
      />
      <Column
        field="costPerLearner"
        :header="t('Cost per learner')"
        sortable
      >
        <template #body="{ data }">
          {{ data.costPerLearner !== null ? formatCurrency(data.costPerLearner) : "—" }}
        </template>
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
              icon="mdi-pencil"
              :label="t('Set cost')"
              @click="openCostDialog(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="costDialog"
      :title="t('Set cost')"
      :style="{ width: '400px' }"
    >
      <div class="flex flex-col gap-4">
        <p class="text-sm text-gray-600">{{ editingSession?.title }}</p>
        <BaseInputNumber
          id="sessionCost"
          name="session_cost"
          v-model="editingCost"
          :label="t('Total cost')"
          :min="0"
          :step="0.01"
          mode="decimal"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
        />
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          severity="secondary"
          @click="costDialog = false"
        />
        <BaseButton
          :label="t('Save')"
          :loading="isSaving"
          icon="save"
          @click="saveCost"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../../composables/notification"
import BaseButton from "../../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../../components/layout/SectionHeader.vue"
import BaseCalendar from "../../../components/basecomponents/BaseCalendar.vue"
import BaseTable from "../../../components/basecomponents/BaseTable.vue"
import BaseDialog from "../../../components/basecomponents/BaseDialog.vue"
import BaseInputNumber from "../../../components/basecomponents/BaseInputNumber.vue"
import baseService from "../../../services/baseService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()

const isLoading = ref(false)
const isSaving = ref(false)
const items = ref([])
const dateRange = ref(null)
const costDialog = ref(false)
const editingSession = ref(null)
const editingCost = ref(0)

function formatCurrency(value) {
  return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)
}

function buildDateParams() {
  if (!Array.isArray(dateRange.value) || dateRange.value.length < 2) return null
  const [start, end] = dateRange.value
  if (!start || !end) return null
  const fmt = (d) => d.toISOString().slice(0, 10)
  return { "accessStartDate[after]": fmt(start), "accessStartDate[before]": fmt(end) }
}

async function load() {
  const params = buildDateParams()
  if (!params) return
  isLoading.value = true
  try {
    const response = await baseService.get("/api/hr_roi/courses", params)
    items.value = response["hydra:member"] ?? response
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isLoading.value = false
  }
}

function openCostDialog(session) {
  editingSession.value = session
  editingCost.value = session.cost ?? 0
  costDialog.value = true
}

async function saveCost() {
  if (!editingSession.value) return
  isSaving.value = true
  try {
    await baseService.post(`/api/hr/sessions/${editingSession.value.sessionId}/cost`, {
      cost: editingCost.value,
    })
    editingSession.value.cost = editingCost.value
    editingSession.value.costPerLearner =
      editingSession.value.learnerCount > 0
        ? Math.round((editingCost.value / editingSession.value.learnerCount) * 100) / 100
        : null
    costDialog.value = false
    showSuccessNotification(t("Cost updated"))
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isSaving.value = false
  }
}
</script>
