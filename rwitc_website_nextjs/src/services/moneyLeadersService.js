import { API_URL } from "./api";

export async function getMoneyLeaders(type) {

    try {

        const response = await fetch(
            `${API_URL}/erp_money_leaders_get_api.php?type=${type}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch money leaders");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(json.error || "Failed to fetch money leaders");
        }

        const data = json.data;

        return {
            html: data?.html || "",
            updatedAt: data?.updated_at || null,
            available: data?.available ?? false
        };

    } catch (error) {

        console.error("Money Leaders Error :", error);

        throw error;

    }

}