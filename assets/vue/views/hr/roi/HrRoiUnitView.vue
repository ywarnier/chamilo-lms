<template>
  <div>
    <SectionHeader :title="t('ROI by organizational unit')" />

    <div class="mb-4 flex flex-wrap items-end gap-4">
      <BaseSelect
        id="unitSelect"
        v-model="selectedUnitId"
        :label="t('Unit')"
        :options="unitOptions"
        option-label="label"
        option-value="value"
        :placeholder="t('Select a unit')"
      />
      <div class="flex items-center gap-2">
        <BaseCheckbox
          id="filterByDate"
          v-model="filterByDate"
          :label="t('Filter by date range')"
        />
      </div>
      <div
        v-if="filterByDate"
        class="flex flex-col gap-1"
      >
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
        :disabled="!selectedUnitId"
        @click="load"
      />
    </div>

    <BaseTable
      :is-loading="isLoading"
      :values="items"
    >
      <Column
        field="userFullName"
        :header="t('User')"
        sortable
      />
      <Column
        field="sessionsCount"
        :header="t('Number of sessions')"
        sortable
      />
      <Column
        field="totalInvestment"
        :header="t('Total investment')"
        sortable
      >
        <template #body="{ data }">{{ formatCurrency(data.totalInvestment) }}</template>
      </Column>
      <Column
        field="averageCostPerSession"
        :header="t('Average cost by session')"
        sortable
      >
        <template #body="{ data }">{{ formatCurrency(data.averageCostPerSession) }}</template>
      </Column>
    </BaseTable>

    <div
      v-if="items.length"
      class="mt-4 flex justify-end"
    >
      <div class="rounded bg-gray-100 px-6 py-3 text-right">
        <div class="text-sm text-gray-500">{{ t("Total investment") }}</div>
        <div class="text-lg font-semibold">{{ formatCurrency(unitTotalInvestment) }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../../composables/notification"
import baseService from "../../../services/baseService"
import BaseButton from "../../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../../components/basecomponents/BaseCheckbox.vue"
import BaseSelect from "../../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../../components/layout/SectionHeader.vue"

const { t } = useI18n()
const { showErrorNotification } = useNotification()

const isLoading = ref(false)
const items = ref([])
const selectedUnitId = ref(null)
const unitOptions = ref([])
const filterByDate = ref(false)
const dateRange = ref(null)

const unitTotalInvestment = computed(() => items.value.reduce((acc, item) => acc + item.totalInvestment, 0))

function formatCurrency(value) {
  return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)
}

onMounted(async () => {
  try {
    const response = await baseService.get("/api/business_units", { pagination: false })
    const units = response["hydra:member"] ?? response
    unitOptions.value = units.map((u) => ({ label: u.title, value: u.id }))
  } catch {
    showErrorNotification(t("An error occurred"))
  }
})

function buildParams() {
  const params = { unit: selectedUnitId.value }
  if (filterByDate.value && Array.isArray(dateRange.value) && dateRange.value[0] && dateRange.value[1]) {
    const fmt = (d) => d.toISOString().slice(0, 10)
    params["accessStartDate[after]"] = fmt(dateRange.value[0])
    params["accessStartDate[before]"] = fmt(dateRange.value[1])
  }
  return params
}

async function load() {
  if (!selectedUnitId.value) return
  isLoading.value = true
  try {
    const response = await baseService.get("/api/hr_roi/unit", buildParams())
    items.value = response["hydra:member"] ?? response
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isLoading.value = false
  }
}
</script>
