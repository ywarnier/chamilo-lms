<template>
  <div class="hr-my-career-plan">
    <SectionHeader :title="t('My career plan')" />

    <div
      v-if="loading"
      class="flex justify-center py-16"
    >
      <span class="text-gray-400">{{ t("Loading...") }}</span>
    </div>

    <!-- Empty state: no position assigned -->
    <div
      v-else-if="!currentPositions.length"
      class="flex flex-col items-center py-16 gap-4 text-center"
    >
      <BaseIcon
        class="text-gray-300"
        icon="mdi-briefcase-off-outline"
        size="48px"
      />
      <p class="text-gray-500 text-base">{{ t("You must be assigned to a position to see your career plan.") }}</p>
    </div>

    <div
      v-else
      class="space-y-6"
    >
      <!-- Current positions -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg px-6 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-500 mb-2">{{ t("Your current position") }}</p>
        <div class="flex flex-wrap gap-3">
          <div
            v-for="pos in currentPositions"
            :key="pos.id"
            class="bg-white border border-blue-200 rounded px-4 py-2 text-sm"
          >
            <p class="font-semibold text-gray-800">{{ pos.title }}</p>
            <p class="text-gray-500 text-xs">
              <span v-if="pos.parentBusinessUnitTitle">{{ pos.parentBusinessUnitTitle }} / </span>
              {{ pos.businessUnitTitle }}
            </p>
          </div>
        </div>
      </div>

      <!-- No targets -->
      <div
        v-if="!targets.length"
        class="text-center py-8 text-gray-400 italic"
      >
        {{ t("No career targets found for your current position.") }}
      </div>

      <!-- Target positions with skill gap bars -->
      <div
        v-for="target in targets"
        :key="target.id"
        class="bg-white border border-gray-200 rounded-lg overflow-hidden"
      >
        <!-- Target header -->
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
          <p class="font-semibold text-gray-900">{{ target.title }}</p>
          <p class="text-sm text-gray-500">{{ target.businessUnitTitle }}</p>
        </div>

        <!-- No skills defined -->
        <div
          v-if="!target.skills.length"
          class="px-6 py-4 text-sm text-gray-400 italic"
        >
          {{ t("No required skills defined for this position.") }}
        </div>

        <!-- Skill gap bars -->
        <div
          v-else
          class="px-6 py-4 space-y-5"
        >
          <!-- Legend -->
          <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1">
              <span class="inline-block w-3 h-3 rounded-sm bg-amber-700 opacity-60" />
              {{ t("Required level") }}
            </span>
            <span class="flex items-center gap-1">
              <span class="inline-block w-3 h-3 rounded-sm bg-green-500" />
              {{ t("Acquired level") }}
            </span>
          </div>

          <div
            v-for="skill in target.skills"
            :key="skill.skillId"
            class="space-y-1"
          >
            <div class="flex items-center justify-between text-sm">
              <span class="font-medium text-gray-800">{{ skill.skillTitle }}</span>
              <span
                :class="skill.isMet ? 'text-green-600' : 'text-amber-600'"
                class="text-xs font-medium"
              >
                {{ skill.isMet ? t("Met") : t("Gap") }}
              </span>
            </div>

            <!-- Stacked bar -->
            <div class="relative h-5 bg-gray-100 rounded overflow-hidden">
              <!-- Required level bar (brown/amber, background) -->
              <div
                v-if="skill.requiredPercentage !== null"
                :style="{ width: skill.requiredPercentage + '%' }"
                class="absolute inset-y-0 left-0 bg-amber-700 opacity-30 rounded"
              />
              <!-- Acquired level bar (green, overlaid) -->
              <div
                :style="{ width: skill.acquiredPercentage + '%' }"
                class="absolute inset-y-0 left-0 bg-green-500 opacity-80 rounded transition-all duration-500"
              />
              <!-- Level labels -->
              <div
                class="absolute inset-0 flex items-center justify-between px-2 text-xs text-gray-600 font-medium pointer-events-none"
              >
                <span />
                <span
                  v-if="skill.acquiredLevelShortTitle || skill.requiredLevelShortTitle"
                  class="text-white text-shadow-sm"
                >
                  {{ skill.acquiredLevelShortTitle ?? "—" }} / {{ skill.requiredLevelShortTitle ?? "—" }}
                </span>
              </div>
            </div>

            <!-- Axis labels -->
            <div class="flex justify-between text-xs text-gray-400">
              <span>0</span>
              <span class="text-gray-500">
                {{ t("Acquired:") }} <strong>{{ skill.acquiredLevelTitle ?? t("None") }}</strong>
                &nbsp;·&nbsp;
                {{ t("Required:") }} <strong>{{ skill.requiredLevelTitle ?? t("Any") }}</strong>
              </span>
              <span>{{ t("Max") }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()

const loading = ref(true)
const currentPositions = ref([])
const targets = ref([])

async function load() {
  loading.value = true
  try {
    const data = await baseService.get("/api/my_career_plan")
    currentPositions.value = data?.currentPositions ?? []
    targets.value = data?.targets ?? []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
