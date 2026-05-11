export default {
  path: "/job-offers",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      name: "JobOfferPublicList",
      path: "",
      meta: {
        showBreadcrumb: false,
      },
      component: () => import("../views/job-offers/JobOfferPublicListView.vue"),
    },
    {
      name: "JobOfferDetail",
      path: ":id",
      meta: {
        showBreadcrumb: false,
      },
      component: () => import("../views/job-offers/JobOfferDetailView.vue"),
    },
    {
      name: "JobOfferMyApplications",
      path: "my-applications",
      meta: {
        requiresAuth: true,
        showBreadcrumb: false,
      },
      component: () => import("../views/job-offers/JobOfferMyApplicationsView.vue"),
    },
  ],
}
