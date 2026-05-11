<template>
  <div class="hr-career-plan">
    <SectionHeader :title="t('Career plan overview')" />

    <div
      v-if="loading"
      class="flex justify-center py-16"
    >
      <span class="text-gray-400">{{ t("Loading...") }}</span>
    </div>

    <div
      v-else-if="!groups.length"
      class="text-center py-16 text-gray-400"
    >
      {{ t("No function-unit associations configured yet.") }}
    </div>

    <div
      v-else
      class="space-y-8"
    >
      <div
        v-for="group in groups"
        :key="group.businessUnitId"
        class="bg-white border border-gray-200 rounded-lg overflow-hidden"
      >
        <!-- Business unit header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
          <div class="flex items-center gap-2">
            <span
              v-if="group.parentTitle"
              class="text-sm text-gray-500"
              >{{ group.parentTitle }} /</span
            >
            <h2 class="text-base font-semibold text-gray-800">{{ group.businessUnitTitle }}</h2>
          </div>
        </div>

        <!-- Functions table -->
        <div class="divide-y divide-gray-100">
          <div
            v-for="fn in group.functions"
            :key="fn.id"
            class="px-6 py-4"
          >
            <div class="flex flex-col gap-3">
              <div>
                <p class="font-medium text-gray-900">{{ fn.title }}</p>
                <p class="text-sm text-gray-500">{{ fn.professionalFunctionTitle }}</p>
                <p
                  v-if="fn.description"
                  class="text-sm text-gray-500 mt-1"
                  v-html="fn.description"
                />
              </div>

              <div
                v-if="fn.skills.length"
                class="flex flex-wrap gap-2"
              >
                <span
                  v-for="skill in fn.skills"
                  :key="skill.skillId"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs bg-blue-50 text-blue-700 border border-blue-100"
                >
                  <span class="font-medium">{{ skill.skillTitle }}</span>
                  <span
                    v-if="skill.requiredLevelTitle"
                    class="text-blue-500"
                    >— {{ skill.requiredLevelTitle }}</span
                  >
                </span>
              </div>

              <p
                v-else
                class="text-xs text-gray-400 italic"
              >
                {{ t("No required skills defined.") }}
              </p>
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
import SectionHeader from "../../components/layout/SectionHeader.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()

const groups = ref([])
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    const data = await baseService.get("/api/career_plan/overview")
    groups.value = data?.["hydra:member"] ?? []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
