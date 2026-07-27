"use client";

import { useState } from "react";
import {
  FaUser,
  FaEnvelope,
  FaCommentDots,
  FaLightbulb,
  FaCheckCircle,
  FaHeadset,
} from "react-icons/fa";
import "./Suggestion.css";

export default function Suggestion() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    message: "",
  });

  const handleChange = (event) => {
    const { name, value } = event.target;

    setFormData((current) => ({
      ...current,
      [name]: value,
    }));
  };

  const handleSubmit = (event) => {
    event.preventDefault();

    // Replace this with your existing API submission logic.
    console.log("Suggestion submitted:", formData);
  };

  return (
    <section className="suggestionPage">
      <div className="suggestionWrapper">

        {/* LEFT IMAGE */}
        <div className="suggestionImageSide">
          <div className="suggestionImageShape">
            <img
              src="/image/horse_race2.jpg"
              alt="Royal Western India Turf Club racecourse"
            />
          </div>

          <div className="suggestionCurveLight" />
        </div>

        {/* MIDDLE INFORMATION */}
        <div className="suggestionInfoSide">
          <h1>Share Your Suggestions</h1>
          <span className="suggestionDivider" />

          <p className="suggestionIntro">
            Your ideas help us improve. Share your suggestion or feedback and
            our team will review it carefully.
          </p>

          <div className="suggestionDetailsList">
            <div className="suggestionDetailItem">
              <span className="suggestionDetailIcon">
                <FaLightbulb />
              </span>

              <div className="suggestionDetailText">
                <h4>Share Your Idea</h4>
                <p>Tell us what you would like us to improve.</p>
              </div>
            </div>

            <div className="suggestionDetailItem">
              <span className="suggestionDetailIcon">
                <FaCheckCircle />
              </span>

              <div className="suggestionDetailText">
                <h4>Every Suggestion Matters</h4>
                <p>Every genuine submission is reviewed by our team.</p>
              </div>
            </div>

            <div className="suggestionDetailItem">
              <span className="suggestionDetailIcon">
                <FaHeadset />
              </span>

              <div className="suggestionDetailText">
                <h4>Need Assistance?</h4>
                <p>Our support team is available to help you.</p>
              </div>
            </div>
          </div>
        </div>

        {/* RIGHT FORM */}
        <form className="suggestionFormCard" onSubmit={handleSubmit}>
          <div className="suggestionFormRow">
            <div className="suggestionField">
              <label className="suggestionFieldLabel" htmlFor="suggestion-name">
                <FaUser /> Name
              </label>

              <input
                id="suggestion-name"
                type="text"
                name="name"
                placeholder="Enter Name"
                value={formData.name}
                onChange={handleChange}
              />
            </div>

            <div className="suggestionField">
              <label className="suggestionFieldLabel" htmlFor="suggestion-email">
                <FaEnvelope /> Email
              </label>

              <input
                id="suggestion-email"
                type="email"
                name="email"
                placeholder="Enter Email"
                value={formData.email}
                onChange={handleChange}
              />
            </div>
          </div>

          <div className="suggestionField">
            <label
              className="suggestionFieldLabel"
              htmlFor="suggestion-message"
            >
              <FaCommentDots /> Suggestion
            </label>

            <textarea
              id="suggestion-message"
              name="message"
              placeholder="Type Your Suggestion"
              value={formData.message}
              onChange={handleChange}
            />
          </div>

          {/* Replace this placeholder with your real Google reCAPTCHA component if available. */}
          <div className="suggestionCaptchaBox">
            <div className="suggestionCaptchaContent">
              <div className="suggestionCaptchaLeft">
                <span className="suggestionCaptchaCheckbox" />
                <span>I&apos;m not a robot</span>
              </div>

              <p className="suggestionCaptchaNotice">
                reCAPTCHA is changing its terms of service.
                <br />
                <a href="#">Take action.</a>
              </p>
            </div>

            <div className="suggestionCaptchaBadge">
              <img
                src="/image/recaptcha-logo.png"
                alt="reCAPTCHA"
              />
              <span>reCAPTCHA</span>
              <small>Privacy - Terms</small>
            </div>
          </div>

          <button type="submit" className="suggestionSubmitBtn">
            Submit
          </button>
        </form>
      </div>
    </section>
  );
}
