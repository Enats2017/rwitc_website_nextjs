"use client";

import { useEffect, useRef, useState } from "react";
import Script from "next/script";
import "./Contact.css";
import { UPLOAD_URL } from "../../services/api";
import { sendContactMessage } from "../../services/contactService";
import {
    FaUser,
    FaEnvelope,
    FaCommentDots,
    FaPhoneAlt,
    FaMapMarkerAlt,
} from "react-icons/fa";

// Public reCAPTCHA v2 site key (safe to expose in frontend code)
const RECAPTCHA_SITE_KEY = process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY;

// The reCAPTCHA v2 widget has a fixed size (304 x 78)
const CAPTCHA_WIDTH = 304;
const CAPTCHA_HEIGHT = 78;

export default function Contact() {
    const recaptchaRef = useRef(null);
    const widgetIdRef = useRef(null);
    const formRef = useRef(null);
    const captchaWrapRef = useRef(null);

    const [submitting, setSubmitting] = useState(false);
    const [statusMessage, setStatusMessage] = useState(null);
    const [statusType, setStatusType] = useState(null); // "success" | "error"
    const [captchaScale, setCaptchaScale] = useState(1);

    // Scale the captcha down on very narrow screens so it never overflows
    useEffect(() => {
        const wrap = captchaWrapRef.current;
        if (!wrap || typeof ResizeObserver === "undefined") return;

        const update = () => {
            const available = wrap.clientWidth;
            if (available > 0) {
                setCaptchaScale(Math.min(1, available / CAPTCHA_WIDTH));
            }
        };

        update();
        const observer = new ResizeObserver(update);
        observer.observe(wrap);

        return () => observer.disconnect();
    }, []);

    function renderRecaptcha() {
        if (!RECAPTCHA_SITE_KEY) return;
        if (!window.grecaptcha || !window.grecaptcha.render) return;
        if (!recaptchaRef.current || widgetIdRef.current !== null) return;

        widgetIdRef.current = window.grecaptcha.render(recaptchaRef.current, {
            sitekey: RECAPTCHA_SITE_KEY,
        });
    }

    function resetRecaptcha() {
        if (window.grecaptcha && widgetIdRef.current !== null) {
            window.grecaptcha.reset(widgetIdRef.current);
        }
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
            setStatusMessage("Please complete the reCAPTCHA verification.");
            return;
        }

        setSubmitting(true);

        try {
            await sendContactMessage({ name, email, message, recaptchaToken });
            setStatusType("success");
            setStatusMessage(
                "Thank you. Your message has been received and we will respond shortly."
            );
            formRef.current?.reset();
            resetRecaptcha();
        } catch (error) {
            setStatusType("error");
            setStatusMessage(
                error.message ||
                    "We could not send your message. Please try again."
            );
            resetRecaptcha();
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <main className="contactPage">
            {/* Google reCAPTCHA v2 script (explicit render) */}
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

            {/* Background */}
            <img
                src={`${UPLOAD_URL}/body_img5.jpeg`}
                className="contactBgImage"
            />
            <div className="contactBgOverlay"></div>

            <section className="contactShell">
                {/* Left: introduction and contact details */}
                <div className="contactIntro">
                    <span className="contactEyebrow">Get in Touch</span>
                    <h1>Contact Us</h1>
                    <span className="contactIntroAccent"></span>

                    <p className="contactIntroText">
                        For enquiries, feedback or assistance, please write to
                        us using the form. Our team will review your message and
                        respond at the earliest.
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
                                <strong>Address</strong>
                                <p>Mahalaxmi, Mumbai - 400034</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Right: form card */}
                <div className="contactFormCard">
                    <h2 className="contactFormTitle">Send a message</h2>
                    <p className="contactFormSub">
                        Please share your details and enquiry. We will reply by
                        email.
                    </p>

                    <form
                        className="contactForm"
                        ref={formRef}
                        onSubmit={handleSubmit}
                    >
                        {/* Name */}
                        <div className="contactField">
                            <label htmlFor="contact-name">
                                <FaUser className="contactFieldLabelIcon" /> Full Name
                            </label>
                            <input
                                type="text"
                                id="contact-name"
                                name="name"
                                placeholder="Your full name"
                                autoComplete="name"
                                required
                            />
                        </div>

                        {/* Email */}
                        <div className="contactField">
                            <label htmlFor="contact-email">
                                <FaEnvelope className="contactFieldLabelIcon" /> Email Address
                            </label>
                            <input
                                type="email"
                                id="contact-email"
                                name="email"
                                placeholder="Your email address"
                                autoComplete="email"
                                required
                            />
                        </div>

                        {/* Message */}
                        <div className="contactField">
                            <label htmlFor="contact-message">
                                <FaCommentDots className="contactFieldLabelIcon" /> Message
                            </label>
                            <textarea
                                id="contact-message"
                                name="message"
                                placeholder="Please describe your enquiry"
                                rows="4"
                                required
                            ></textarea>
                        </div>

                        {/* reCAPTCHA (scaled to fit narrow screens) */}
                        <div
                            className="contactCaptcha"
                            ref={captchaWrapRef}
                            style={{ height: CAPTCHA_HEIGHT * captchaScale }}
                        >
                            <div
                                className="contactCaptchaInner"
                                style={{
                                    width: CAPTCHA_WIDTH,
                                    height: CAPTCHA_HEIGHT,
                                    transform: `scale(${captchaScale})`,
                                }}
                            >
                                <div ref={recaptchaRef}></div>
                            </div>
                        </div>

                        {!RECAPTCHA_SITE_KEY && (
                            <p className="contactStatusError" role="alert">
                                reCAPTCHA is not configured. Please contact the
                                site administrator.
                            </p>
                        )}

                        {/* Status message */}
                        {statusMessage && (
                            <p
                                role="status"
                                aria-live="polite"
                                className={
                                    statusType === "success"
                                        ? "contactStatusSuccess"
                                        : "contactStatusError"
                                }
                            >
                                {statusMessage}
                            </p>
                        )}

                        {/* Submit */}
                        <button
                            type="submit"
                            className="contactSubmitButton"
                            disabled={submitting}
                        >
                            <span>
                                {submitting ? "Sending..." : "Submit Enquiry"}
                            </span>
                        </button>
                    </form>
                </div>
            </section>
        </main>
    );
}