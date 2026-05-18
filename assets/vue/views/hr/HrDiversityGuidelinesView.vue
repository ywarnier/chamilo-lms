<template>
  <div class="hr-diversity-guidelines">
    <SectionHeader :title="t('Diversity guidelines')" />

    <div
      v-if="criteria.length === 0 && !isLoading"
      class="text-gray-500 text-sm mt-4"
    >
      {{ t("No diversity criteria found. Add criteria first.") }}
    </div>

    <div
      v-else
      class="flex gap-6 mt-4"
    >
      <!-- Sidebar -->
      <nav class="w-48 shrink-0">
        <ul class="space-y-1">
          <li
            v-for="item in criteria"
            :key="item['@id']"
          >
            <BaseButton
              :label="item.title"
              :type="selected && selected.id === item.id ? 'primary' : 'tertiary-alternative'"
              class="w-full text-left"
              icon="information"
              size="small"
              @click="selectCriteria(item)"
            />
          </li>
        </ul>
      </nav>

      <!-- Chart area -->
      <div class="flex-1 min-w-0">
        <div
          v-if="isLoadingStats"
          class="flex items-center justify-center h-48 text-gray-400 text-sm"
        >
          {{ t("Loading...") }}
        </div>

        <div
          v-else-if="selected"
          class="space-y-4"
        >
          <h2 class="text-lg font-semibold text-gray-800">
            {{ selected.title }}
          </h2>
          <p
            v-if="selected.description"
            class="text-sm text-gray-600"
          >
            {{ selected.description }}
          </p>

          <div
            v-if="stats && stats.unfilledPercent > 0"
            :class="stats.unfilledPercent >= 50 ? 'text-danger' : 'text-warning'"
            class="text-body-2 px-3 py-2 rounded"
          >
            {{ t("%s of staff have not filled in this criterion.", [t("%s %", [stats.unfilledPercent])]) }}
          </div>

          <div
            v-if="stats && stats.labels.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 gap-6"
          >
            <div>
              <PrimeChart
                :data="barChartData"
                :options="barChartOptions"
                class="w-full"
                type="bar"
              />
            </div>
            <div>
              <PrimeChart
                :data="pieChartData"
                :options="pieChartOptions"
                class="w-full"
                type="pie"
              />
            </div>
          </div>

          <div
            v-else
            class="text-sm text-gray-500"
          >
            {{ t("No data available for this criterion.") }}
          </div>
        </div>

        <div
          v-else
          class="text-sm text-gray-400 mt-8"
        >
          {{ t("Select a criterion from the list to view its statistics.") }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import PrimeChart from "primevue/chart"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import * as diversityCriteriaService from "../../services/hr/diversityCriteriaService"
import baseService from "../../services/baseService"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const { showErrorNotification } = useNotification()

const criteria = ref([])
const selected = ref(null)
const stats = ref(null)
const isLoading = ref(true)
const isLoadingStats = ref(false)

const CHART_COLORS = ["#4F81BD", "#C0504D", "#9BBB59", "#8064A2", "#4BACC6", "#F79646", "#2C4770", "#772C2C"]

function getColors(count) {
  const colors = []
  for (let i = 0; i < count; i++) {
    colors.push(CHART_COLORS[i % CHART_COLORS.length])
  }
  return colors
}

const barChartData = computed(() => {
  if (!stats.value) return {}
  const colors = getColors(stats.value.labels.length)
  return {
    labels: stats.value.labels,
    datasets: [
      {
        label: stats.value.extraFieldTitle,
        data: stats.value.values,
        backgroundColor: colors,
      },
    ],
  }
})

const pieChartData = computed(() => {
  if (!stats.value) return {}
  const colors = getColors(stats.value.labels.length)
  return {
    labels: stats.value.labels,
    datasets: [
      {
        data: stats.value.percentages,
        backgroundColor: colors,
      },
    ],
  }
})

const barChartOptions = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
}

const pieChartOptions = {
  responsive: true,
  plugins: { legend: { position: "bottom" } },
}

async function loadCriteria() {
  isLoading.value = true
  try {
    criteria.value = await diversityCriteriaService.getAll({ pagination: false })
    if (criteria.value.length > 0) {
      await selectCriteria(criteria.value[0])
    }
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

async function selectCriteria(item) {
  selected.value = item
  stats.value = null
  isLoadingStats.value = true
  try {
    stats.value = await baseService.get(`/hr/diversity-guidelines-data/${item.id}`)
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoadingStats.value = false
  }
}

onMounted(loadCriteria)
</script>
