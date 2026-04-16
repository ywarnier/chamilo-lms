<template>
  <div class="p-6 space-y-6">
    <h2 class="text-lg font-semibold text-gray-700">
      {{ t("Human Resources") }}
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="block in visibleBlocks"
        :key="block.id"
        class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden"
      >
        <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100 bg-gray-50">
          <i
            :class="`mdi mdi-${block.mdiIcon} text-xl text-gray-500`"
            aria-hidden="true"
          />
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
            {{ t(block.title) }}
          </h3>
        </div>
        <ul class="py-2">
          <li
            v-for="item in visibleItems(block)"
            :key="item.label"
            class="px-5 py-1"
          >
            <router-link
              v-if="item.route"
              :to="{ name: item.route }"
              class="text-sm text-blue-600 hover:text-blue-800 hover:underline"
            >
              {{ t(item.label) }}
            </router-link>
            <span
              v-else
              class="text-sm text-gray-400 italic"
            >
              {{ t(item.label) }}
            </span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const securityStore = useSecurityStore()

const blocks = [
  {
    id: "hr-staff",
    mdiIcon: "account-group",
    title: "Staff",
    items: [
      { label: "User list" },
      { label: "Geographic zones", route: "HrGeographicZones", adminOnly: true },
      { label: "Branches", route: "HrBranches" },
      { label: "Staff statuses", route: "HrStaffStatuses" },
      { label: "Contract types", route: "HrContractTypes" },
      { label: "Business units", route: "HrBusinessUnits" },
    ],
  },
  {
    id: "hr-organization",
    mdiIcon: "sitemap",
    title: "Organization",
    items: [
      { label: "Professional functions" },
      { label: "Positions" },
      { label: "Units and positions list" },
      { label: "Assign position to user" },
      { label: "Organizational chart" },
    ],
  },
  {
    id: "hr-performance",
    mdiIcon: "chart-line",
    title: "Performance Management",
    items: [
      { label: "Appraisal periods" },
      { label: "Appraisal templates" },
      { label: "Performance appraisals" },
      { label: "Activities", route: "HrActivities", adminOnly: true },
      { label: "Performance objectives", route: "HrPerformanceObjectives", adminOnly: true },
    ],
  },
  {
    id: "hr-recruitment",
    mdiIcon: "account-multiple-plus",
    title: "Recruitment",
    items: [
      { label: "Recruitment stages" },
      { label: "Recruitment processes" },
      { label: "Job offers" },
    ],
  },
  {
    id: "hr-skills",
    mdiIcon: "certificate",
    title: "Skills",
    items: [
      { label: "Skill profiles" },
      { label: "Skill levels" },
      { label: "Manage skills" },
      { label: "Team skills goals" },
    ],
  },
  {
    id: "hr-benefits",
    mdiIcon: "gift-outline",
    title: "Benefits",
    items: [
      { label: "Benefit tags" },
      { label: "Benefits" },
      { label: "Assign benefits" },
      { label: "Assigned benefits" },
    ],
  },
  {
    id: "hr-roi",
    mdiIcon: "currency-usd",
    title: "ROI and training",
    items: [
      { label: "ROI by course" },
      { label: "ROI by person" },
      { label: "ROI by organizational unit" },
      { label: "Training needs assessment" },
      { label: "Work climate surveys" },
    ],
  },
  {
    id: "hr-diversity",
    mdiIcon: "scale-balance",
    title: "Diversity and social responsibility",
    items: [
      { label: "Diversity criteria" },
      { label: "Diversity guidelines" },
      { label: "Social responsibility guidelines" },
    ],
  },
]

const visibleBlocks = computed(() => blocks)

function visibleItems(block) {
  if (securityStore.isAdmin) {
    return block.items
  }
  return block.items.filter((item) => !item.adminOnly)
}
</script>
