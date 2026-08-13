import { API_URL } from "./api";

export async function sendContactMessage({ name, email, message, recaptchaToken }) {

    try {

        const formData = new FormData();

        formData.append("name", name);
        formData.append("email", email);
        formData.append("message", message);
        formData.append("g-recaptcha-response", recaptchaToken);

        const response = await fetch(
            `${API_URL}/email_to_chairman.php`,
            {
                method: "POST",
                body: formData,
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Failed to send message.");
        }

        return result;

    } catch (error) {

        console.error("Contact Form API Error :", error);
        throw error;

    }

}