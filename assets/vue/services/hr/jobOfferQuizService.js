import baseService from "../baseService"

export async function getByJobOffer(jobOfferId) {
  const result = await baseService.getCollection("/api/job_offer_quizes", {
    pagination: false,
    "jobOffer.id": jobOfferId,
  })
  return result.items
}

export async function create(payload) {
  return baseService.post("/api/job_offer_quizes", payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}
