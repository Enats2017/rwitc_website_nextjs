"use client";

import { useRef, useState } from "react";
import Script from "next/script";
import "./Contact.css";
import { UPLOAD_URL } from "../../services/api";
import { sendContactMessage } from "../../services/contactService";

import {
    FaUser,
    FaEnvelope,
    FaCommentDots,
    FaHorse,
    FaPhoneAlt,
    FaMapMarkerAlt,
} from "react-icons/fa";

// Public reCAPTCHA v2 site key (paired with the secret key already
// verified server-side in email_to_chairman.php). Site keys are safe
// to expose in frontend code.

// const RECAPTCHA_SITE_KEY = "6Lcg84giAAAAAI97yR_2PmV6nFxNGfEqtKo-7WMU";  // Live key (used in production)
const RECAPTCHA_SITE_KEY = "6Ldq-IEtAAAAANW8QR0KhjOMeHxdvOoiwLF5kSjy";     // Test key (used in development)

export default function Contact() {

    const recaptchaRef = useRef(null);
    const widgetIdRef = useRef(null);
    const formRef = useRef(null);

    const [recaptchaReady, setRecaptchaReady] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [statusMessage, setStatusMessage] = useState(null);
    const [statusType, setStatusType] = useState(null); // "success" | "error"

    function renderRecaptcha() {

        if (!window.grecaptcha || !recaptchaRef.current) return;
        if (widgetIdRef.current !== null) return;

        widgetIdRef.current = window.grecaptcha.render(recaptchaRef.current, {
            sitekey: RECAPTCHA_SITE_KEY,
        });

        setRecaptchaReady(true);

    }

    const handleSubmit = async (event) => {

        event.preventDefault();

        setStatusMessage(null);
        setStatusType(null);

        const formData = new FormData(event.currentTarget);

        const name = formData.get("name");
        const email = formData.get("email");
        const message = formData.get("message");

        const recaptchaToken =
            window.grecaptcha && widgetIdRef.current !== null
                ? window.grecaptcha.getResponse(widgetIdRef.current)
                : "";

        if (!recaptchaToken) {
            setStatusType("error");
            setStatusMessage("Please verify that you're not a robot.");
            return;
        }

        setSubmitting(true);

        try {

            await sendContactMessage({ name, email, message, recaptchaToken });

            setStatusType("success");
            setStatusMessage("Your message has been sent successfully.");

            formRef.current?.reset();

            if (window.grecaptcha && widgetIdRef.current !== null) {
                window.grecaptcha.reset(widgetIdRef.current);
            }

        } catch (error) {

            setStatusType("error");
            setStatusMessage(
                error.message || "Something went wrong. Please try again."
            );

            if (window.grecaptcha && widgetIdRef.current !== null) {
                window.grecaptcha.reset(widgetIdRef.current);
            }

        } finally {

            setSubmitting(false);

        }

    };

    return (
        <main className="contactPage">

            {/* Loads the Google reCAPTCHA v2 script and renders the widget
                once ready (explicit render, since the widget is inside a
                client component that may mount after the script loads). */}
            <Script
                src="https://www.google.com/recaptcha/api.js?render=explicit&onload=onRecaptchaLoadCallback"
                strategy="afterInteractive"
                onReady={() => {
                    window.onRecaptchaLoadCallback = renderRecaptcha;
                    if (window.grecaptcha && window.grecaptcha.render) {
                        renderRecaptcha();
                    }
                }}
            />

            {/* BACKGROUND IMAGE */}
            <img
                src={`${UPLOAD_URL}/body_img5.jpeg`}
                alt="Horse racing at Royal Western India Turf Club"
                className="contactBgImage"
            />
            <div className="contactBgOverlay"></div>

            <section className="contactShell">

                {/* LEFT SIDE INTRO TEXT */}
                <div className="contactIntro">

                    <span className="contactIntroScript">Let's Talk</span>

                    <h1>Contact Us</h1>

                    <div className="contactIntroDivider">
                        <span className="contactIntroLine"></span>
                        <FaHorse className="contactIntroHorse" />
                        <span className="contactIntroLine"></span>
                    </div>

                    <p>
                        We'd love to hear from you.
                        <br />
                        Drop us a message and we'll get back
                        <br />
                        to you as soon as possible.
                    </p>

                    <div className="contactInfoList">

                        <div className="contactInfoItem">

                            <span className="contactInfoIcon">
                                <FaEnvelope />
                            </span>

                            <div>
                                <strong>Email</strong>
                                <p>info@rwitc.com</p>
                            </div>

                        </div>

                        <div className="contactInfoItem">

                            <span className="contactInfoIcon">
                                <FaPhoneAlt />
                            </span>

                            <div>
                                <strong>Phone</strong>
                                <p>+91 22 2493 7536</p>
                            </div>

                        </div>

                        <div className="contactInfoItem">

                            <span className="contactInfoIcon">
                                <FaMapMarkerAlt />
                            </span>

                            <div>
                                <strong>Venue</strong>
                                <p>
                                    Royal Western India Turf Club,
                                    <br />
                                    Mahalaxmi, Mumbai - 400034
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

                {/* RIGHT SIDE FORM CARD */}
                <div className="contactFormCard">

                    <form
                        className="contactForm"
                        ref={formRef}
                        onSubmit={handleSubmit}
                    >

                        {/* NAME */}
                        <div className="contactField">

                            <label htmlFor="contact-name">
                                <FaUser className="contactFieldLabelIcon" />
                                Name
                            </label>

                            <input
                                type="text"
                                id="contact-name"
                                name="name"
                                placeholder="Enter Name"
                                autoComplete="name"
                                required
                            />

                        </div>

                        {/* EMAIL */}
                        <div className="contactField">

                            <label htmlFor="contact-email">
                                <FaEnvelope className="contactFieldLabelIcon" />
                                Email
                            </label>

                            <input
                                type="email"
                                id="contact-email"
                                name="email"
                                placeholder="Enter Email"
                                autoComplete="email"
                                required
                            />

                        </div>

                        {/* MESSAGE */}
                        <div className="contactField">

                            <label htmlFor="contact-message">
                                <FaCommentDots className="contactFieldLabelIcon" />
                                Message
                            </label>

                            <textarea
                                id="contact-message"
                                name="message"
                                placeholder="Type Your Message"
                                rows="4"
                                required
                            ></textarea>

                        </div>

                        {/* REAL GOOGLE reCAPTCHA WIDGET */}
                        <div className="contactCaptcha">
                            <div ref={recaptchaRef}></div>
                        </div>

                        {/* STATUS MESSAGE */}
                        {statusMessage && (
                            <p
                                className={
                                    statusType === "success"
                                        ? "contactStatusSuccess"
                                        : "contactStatusError"
                                }
                            >
                                {statusMessage}
                            </p>
                        )}

                        {/* SUBMIT BUTTON */}
                        <button
                            type="submit"
                            className="contactSubmitButton"
                            disabled={submitting}
                        >
                            <span>{submitting ? "Sending..." : "Submit"}</span>
                        </button>

                    </form>

                </div>

            </section>

        </main>
    );
}