import baseService from "../baseService"

export async function getAll(params = {}) {
  const { items } = await baseService.getCollection("/api/social_responsibility_goals", {
    pagination: false,
    ...params,
  })

  return items
}

export async function getPublic(language) {
  const params = language ? { language } : {}
  const { items } = await baseService.getCollection("/api/social_responsibility_goals/public", params)

  return items
}

export async function update(iri, payload) {
  return baseService.put(iri, payload)
}
