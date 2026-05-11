import baseService from "../baseService"

export async function getPublic(language) {
  const params = language ? { language } : {}
  const { items } = await baseService.getCollection("/api/social_responsibility_goals/public", params)

  return items
}
