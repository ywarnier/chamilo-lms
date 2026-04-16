export default {
  path: "/hr",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      name: "HrIndex",
      path: "",
      meta: {
        requiresAuth: true,
        requiresHr: true,
        showBreadcrumb: true,
        breadcrumb: "Human Resources",
      },
      component: () => import("../views/hr/HrIndexView.vue"),
    },
    {
      name: "HrActivities",
      path: "activities",
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Activities",
      },
      component: () => import("../views/hr/HrActivityView.vue"),
    },
    {
      name: "HrPerformanceObjectives",
      path: "objectives",
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Performance objectives",
      },
      component: () => import("../views/hr/HrPerformanceObjectiveView.vue"),
    },
    {
      name: "HrGeographicZones",
      path: "geographic-zones",
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Geographic zones",
      },
      component: () => import("../views/hr/HrGeographicZoneView.vue"),
    },
    {
      name: "HrBranches",
      path: "branches",
      meta: {
        requiresAuth: true,
        requiresHr: true,
        showBreadcrumb: true,
        breadcrumb: "Branches",
      },
      component: () => import("../views/hr/HrBranchView.vue"),
    },
    {
      name: "HrStaffStatuses",
      path: "staff-statuses",
      meta: {
        requiresAuth: true,
        requiresHr: true,
        showBreadcrumb: true,
        breadcrumb: "Staff statuses",
      },
      component: () => import("../views/hr/HrStaffStatusView.vue"),
    },
    {
      name: "HrContractTypes",
      path: "contract-types",
      meta: {
        requiresAuth: true,
        requiresHr: true,
        showBreadcrumb: true,
        breadcrumb: "Contract types",
      },
      component: () => import("../views/hr/HrContractTypeView.vue"),
    },
    {
      name: "HrBusinessUnits",
      path: "business-units",
      meta: {
        requiresAuth: true,
        requiresHr: true,
        showBreadcrumb: true,
        breadcrumb: "Business units",
      },
      component: () => import("../views/hr/HrBusinessUnitView.vue"),
    },
  ],
}
