import baseService from "../baseService"

export async function getByProcess(processId) {
  const result = await baseService.getCollection("/api/recruitment_process_trackings", {
    pagination: false,
    process: `/api/recruitment_processes/${processId}`,
  })

  return result.items
}

export async function create(payload) {
  return baseService.post("/api/recruitment_process_trackings", payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}
