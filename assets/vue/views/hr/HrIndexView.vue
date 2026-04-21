<template>
  <div class="admin-index">
    <AdminBlock
      id="block-hr-staff"
      :description="t('Manage staff signaletics: branches, statuses, contract types and organisational structure')"
      :items="staffItems"
      :title="t('Staff')"
      icon="account"
      bg-image="images/bg-block-admin-users.png"
    />

    <AdminBlock
      id="block-hr-organization"
      :description="t('Manage professional functions, positions and the organisational chart')"
      :items="organizationItems"
      :title="t('Organization')"
      icon="list"
      bg-image="images/bg-block-admin-platform.png"
    />

    <AdminBlock
      id="block-hr-performance"
      :description="t('Manage appraisal periods, templates, evaluations, activities and objectives')"
      :items="performanceItems"
      :title="t('Performance Management')"
      icon="tracking"
      bg-image="images/bg-block-admin-tracking.png"
    />

    <AdminBlock
      id="block-hr-recruitment"
      :description="t('Manage recruitment stages, processes and job offers')"
      :items="recruitmentItems"
      :title="t('Recruitment')"
      icon="account-multiple-plus"
      bg-image="images/bg-block-admin-users.png"
    />

    <AdminBlock
      id="block-hr-skills"
      :description="t('Manage skill profiles, levels and team skill goals')"
      :items="skillsItems"
      :title="t('Skills')"
      icon="wheel"
      bg-image="images/bg-block-admin-skills.png"
    />

    <AdminBlock
      id="block-hr-benefits"
      :description="t('Manage benefit categories and assignments to staff')"
      :items="benefitsItems"
      :title="t('Benefits')"
      icon="package"
      bg-image="images/bg-block-admin-gradebook.png"
    />

    <AdminBlock
      id="block-hr-roi"
      :description="t('Track training return on investment and identify training needs')"
      :items="roiItems"
      :title="t('ROI and training')"
      icon="usage"
      bg-image="images/bg-block-admin-tracking.png"
    />

    <AdminBlock
      id="block-hr-diversity"
      :description="t('Manage diversity criteria, guidelines and social responsibility policies')"
      :items="diversityItems"
      :title="t('Diversity and social responsibility')"
      icon="globe"
      bg-image="images/bg-block-admin-settings.png"
    />
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import AdminBlock from "../../components/admin/AdminBlock.vue"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const securityStore = useSecurityStore()

const isAdmin = computed(() => securityStore.isAdmin)

const staffItems = computed(() => {
  const items = [
    { class: "item-hr-user-list", label: t("User list") },
    { class: "item-hr-branches", route: { name: "HrBranches" }, label: t("Branches") },
    { class: "item-hr-staff-statuses", route: { name: "HrStaffStatuses" }, label: t("Staff statuses") },
    { class: "item-hr-contract-types", route: { name: "HrContractTypes" }, label: t("Contract types") },
    { class: "item-hr-business-units", route: { name: "HrBusinessUnits" }, label: t("Business units") },
  ]
  if (isAdmin.value) {
    items.splice(1, 0, {
      class: "item-hr-geographic-zones",
      route: { name: "HrGeographicZones" },
      label: t("Geographic zones"),
    })
  }
  return items
})

const organizationItems = computed(() => [
  {
    class: "item-hr-professional-functions",
    route: { name: "HrProfessionalFunctions" },
    label: t("Professional functions"),
  },
  {
    class: "item-hr-function-in-unit",
    route: { name: "HrFunctionInUnit" },
    label: t("Function-unit associations"),
  },
  {
    class: "item-hr-unit-function-list",
    route: { name: "HrUnitFunctionList" },
    label: t("Unit function list"),
  },
  { class: "item-hr-positions", route: { name: "HrPositions" }, label: t("Positions") },
  {
    class: "item-hr-org-chart",
    route: { name: "OrganizationalChart" },
    label: t("Organizational chart"),
  },
  {
    class: "item-hr-competency-search",
    route: { name: "HrCompetencySearch" },
    label: t("Competency search"),
  },
])

const performanceItems = computed(() => {
  const items = [
    { class: "item-hr-appraisal-periods", label: t("Appraisal periods") },
    { class: "item-hr-appraisal-templates", label: t("Appraisal templates") },
    { class: "item-hr-performance-appraisals", label: t("Performance appraisals") },
  ]
  if (isAdmin.value) {
    items.push(
      { class: "item-hr-activities", route: { name: "HrActivities" }, label: t("Activities") },
      {
        class: "item-hr-performance-objectives",
        route: { name: "HrPerformanceObjectives" },
        label: t("Performance objectives"),
      },
    )
  }
  return items
})

const recruitmentItems = computed(() => [
  { class: "item-hr-recruitment-stages", label: t("Recruitment stages") },
  { class: "item-hr-recruitment-processes", label: t("Recruitment processes") },
  { class: "item-hr-job-offers", label: t("Job offers") },
])

const skillsItems = computed(() => [
  { class: "item-hr-skill-profiles", label: t("Skill profiles") },
  { class: "item-hr-skill-levels", label: t("Skill levels") },
  { class: "item-hr-manage-skills", label: t("Manage skills") },
  { class: "item-hr-team-skills-goals", label: t("Team skills goals") },
])

const benefitsItems = computed(() => [
  { class: "item-hr-benefit-tags", label: t("Benefit tags") },
  { class: "item-hr-benefits", label: t("Benefits") },
  { class: "item-hr-assign-benefits", label: t("Assign benefits") },
  { class: "item-hr-assigned-benefits", label: t("Assigned benefits") },
])

const roiItems = computed(() => [
  { class: "item-hr-roi-course", label: t("ROI by course") },
  { class: "item-hr-roi-person", label: t("ROI by person") },
  { class: "item-hr-roi-unit", label: t("ROI by organizational unit") },
  { class: "item-hr-training-needs", label: t("Training needs assessment") },
  { class: "item-hr-work-climate", label: t("Work climate surveys") },
])

const diversityItems = computed(() => [
  { class: "item-hr-diversity-criteria", label: t("Diversity criteria") },
  { class: "item-hr-diversity-guidelines", label: t("Diversity guidelines") },
  { class: "item-hr-social-responsibility", route: { name: "HrSocialResponsibility" }, label: t("Social responsibility guidelines") },
])
</script>
