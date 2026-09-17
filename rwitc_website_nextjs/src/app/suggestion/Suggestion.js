"use client";

import { useRef, useState } from "react";
import Script from "next/script";
import { FaUser, FaEnvelope, FaCommentDots, FaLightbulb, FaCheckCircle, FaHeadset, FaHorse, } from "react-icons/fa";
import { UPLOAD_URL } from "../../services/api";
import { submitSuggestion } from "../../services/suggestionService";
import "./Suggestion.css";

// Live key (used in production)
// const RECAPTCHA_SITE_KEY = "6Lcg84giAAAAAI97yR_2PmV6nFxNGfEqtKo-7WMU";

// Test key (used in development) — paired with secret key in suggestion_feedback.php
const RECAPTCHA_SITE_KEY = "6Ldq-IEtAAAAANW8QR0KhjOMeHxdvOoiwLF5kSjy";

export default function Suggestion() {
  const recaptchaRef = useRef(null);
  const widgetIdRef = useRef(null);
  const formRef = useRef(null);
  const [submitting, setSubmitting] = useState(false);
  const [statusMessage, setStatusMessage] = useState(null);
  const [statusType, setStatusType] = useState(null);

  function renderRecaptcha() {
    if (!window.grecaptcha || !recaptchaRef.current) return;
    if (widgetIdRef.current !== null) return;
    widgetIdRef.current = window.grecaptcha.render(recaptchaRef.current, {
      sitekey: RECAPTCHA_SITE_KEY,
    });
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
    const result = await submitSuggestion({ name, email, message, captcha: recaptchaToken });

    if (result.success) {
      setStatusType("success");
      setStatusMessage("Suggestion submitted successfully!");
      formRef.current?.reset();
    } else {
      setStatusType("error");
      setStatusMessage(result.message || "Something went wrong.");
    }

    if (window.grecaptcha && widgetIdRef.current !== null) {
      window.grecaptcha.reset(widgetIdRef.current);
    }

    setSubmitting(false);
  };

  return (
    <main className="suggestionPage">
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
        alt="Royal Western India Turf Club racecourse"
        className="suggestionBgImage"
      />
      <div className="suggestionBgOverlay"></div>
      <section className="suggestionShell">
        {/* LEFT SIDE INTRO TEXT */}
        <div className="suggestionIntro">
          <span className="suggestionIntroScript">We're Listening</span>
          <h1>Share Your Suggestions</h1>
          <div className="suggestionIntroDivider">
            <span className="suggestionIntroLine"></span>
            <FaHorse className="suggestionIntroHorse" />
            <span className="suggestionIntroLine"></span>
          </div>

          <p> Your ideas help us improve. <br />
            Share your suggestion or feedback and <br />
            our team will review it carefully.
          </p>

          <div className="suggestionInfoList">
            <div className="suggestionInfoItem">
              <span className="suggestionInfoIcon"> <FaLightbulb /> </span>
              <div>
                <strong>Share Your Idea</strong>
                <p>Tell us what you would like us to improve.</p>
              </div>
            </div>

            <div className="suggestionInfoItem">
              <span className="suggestionInfoIcon"> <FaCheckCircle /> </span>
              <div>
                <strong>Every Suggestion Matters</strong>
                <p>Every genuine submission is reviewed by our team.</p>
              </div>
            </div>

            <div className="suggestionInfoItem">
              <span className="suggestionInfoIcon"> <FaHeadset /> </span>
              <div>
                <strong>Need Assistance?</strong>
                <p>Our support team is available to help you.</p>
              </div>
            </div>
          </div>
        </div>

        {/* RIGHT SIDE FORM CARD */}
        <div className="suggestionFormCard">
          <form className="suggestionForm" ref={formRef} onSubmit={handleSubmit}>

            {/* NAME */}
            <div className="suggestionField">
              <label htmlFor="suggestion-name"> <FaUser className="suggestionFieldLabelIcon" /> Name </label>
              <input id="suggestion-name" type="text" name="name" placeholder="Enter Name" required />
            </div>

            {/* EMAIL */}
            <div className="suggestionField">
              <label htmlFor="suggestion-email"> <FaEnvelope className="suggestionFieldLabelIcon" /> Email </label>
              <input id="suggestion-email" type="email" name="email" placeholder="Enter Email" required />
            </div>

            {/* SUGGESTION */}
            <div className="suggestionField">
              <label htmlFor="suggestion-message"> <FaCommentDots className="suggestionFieldLabelIcon" /> Suggestion </label>
              <textarea id="suggestion-message" name="message" placeholder="Type Your Suggestion" rows="4" required />
            </div>

            {/* REAL GOOGLE reCAPTCHA WIDGET */}
            <div className="suggestionCaptcha">
              <div ref={recaptchaRef}></div>
            </div>

            {/* STATUS MESSAGE */}
            {statusMessage && (
              <p style={{ color: statusType === "success" ? "#0b6d2a" : "#d32f2f", fontWeight: 600 }}>
                {statusMessage}
              </p>
            )}

            {/* SUBMIT BUTTON */}
            <button type="submit" className="suggestionSubmitButton" disabled={submitting}>
              <span>{submitting ? "Submitting..." : "Submit"}</span>
            </button>
          </form>
        </div>
      </section>
    </main>
  );
}