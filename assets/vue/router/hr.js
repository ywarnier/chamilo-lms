export default {
  path: "/hr",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
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
  ],
}
