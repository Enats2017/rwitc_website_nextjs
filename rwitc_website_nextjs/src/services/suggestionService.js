import { API_URL } from "./api";

export async function submitSuggestion(formData) {
    try {
        const form = new FormData();
        form.append("name", formData.name);
        form.append("email", formData.email);
        form.append("message", formData.message);
        form.append("g-recaptcha-response", formData.captcha);

        const response = await fetch(`${API_URL}/suggestion_feedback.php`, {
            method: "POST",
            body: form,
        });

        if (!response.ok) {
            throw new Error("Failed to submit suggestion");
        }

        const data = await response.json();
        return data;

    } catch (error) {
        console.error("Suggestion Submit Error :", error);
        return { success: false, message: "Something went wrong" };
    }
}