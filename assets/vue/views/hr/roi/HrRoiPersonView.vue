<template>
  <div>
    <SectionHeader :title="t('ROI by person')" />

    <div class="mb-4 flex flex-wrap items-end gap-4">
      <BaseAutocomplete
        id="userSearch"
        v-model="selectedUser"
        :label="t('User')"
        option-label="label"
        :search="searchUsers"
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
        :disabled="!selectedUser"
        @click="load"
      />
      <BaseButton
        v-if="items.length"
        :label="t('Export CSV')"
        icon="mdi-file-delimited-outline"
        severity="secondary"
        @click="exportCsv"
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
        field="userShare"
        :header="t('User share')"
        sortable
      >
        <template #body="{ data }">
          {{ data.userShare !== null ? formatCurrency(data.userShare) : "—" }}
        </template>
      </Column>
    </BaseTable>

    <div
      v-if="items.length"
      class="mt-4 flex justify-end"
    >
      <div class="rounded bg-gray-100 px-6 py-3 text-right">
        <div class="text-sm text-gray-500">{{ t("Total investment") }}</div>
        <div class="text-lg font-semibold">{{ formatCurrency(totalInvestment) }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../../composables/notification"
import baseService from "../../../services/baseService"
import BaseAutocomplete from "../../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../../components/basecomponents/BaseCheckbox.vue"
import BaseTable from "../../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../../components/layout/SectionHeader.vue"

const { t } = useI18n()
const { showErrorNotification } = useNotification()

const isLoading = ref(false)
const items = ref([])
const selectedUser = ref(null)
const filterByDate = ref(false)
const dateRange = ref(null)

const totalInvestment = computed(() => items.value.reduce((acc, item) => acc + (item.userShare ?? 0), 0))

function formatCurrency(value) {
  return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)
}

async function searchUsers(query) {
  try {
    const response = await baseService.get("/api/users", { search: query, pagination: true })
    const users = response["hydra:member"] ?? response
    return users.map((u) => ({ label: u.fullName ?? u.username, value: u.id }))
  } catch {
    return []
  }
}

function buildParams() {
  const params = { user: selectedUser.value?.value }
  if (filterByDate.value && Array.isArray(dateRange.value) && dateRange.value[0] && dateRange.value[1]) {
    const fmt = (d) => d.toISOString().slice(0, 10)
    params["session.accessStartDate[after]"] = fmt(dateRange.value[0])
    params["session.accessStartDate[before]"] = fmt(dateRange.value[1])
  }
  return params
}

async function load() {
  if (!selectedUser.value) return
  isLoading.value = true
  try {
    const response = await baseService.get("/api/hr_roi/person", buildParams())
    items.value = response["hydra:member"] ?? response
  } catch {
    showErrorNotification(t("An error occurred"))
  } finally {
    isLoading.value = false
  }
}

function exportCsv() {
  const headers = [
    t("Session"),
    t("Start date"),
    t("End date"),
    t("Total cost"),
    t("Number of learners"),
    t("User share"),
  ]
  const rows = items.value.map((item) => [
    item.title,
    item.accessStartDate ?? "",
    item.accessEndDate ?? "",
    item.cost,
    item.learnerCount,
    item.userShare ?? "",
  ])
  const csv = [headers, ...rows].map((r) => r.join(";")).join("\n")
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" })
  const url = URL.createObjectURL(blob)
  const a = document.createElement("a")
  a.href = url
  a.download = "roi_person.csv"
  a.click()
  URL.revokeObjectURL(url)
}
</script>
