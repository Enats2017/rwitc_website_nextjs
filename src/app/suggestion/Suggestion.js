"use client";

import { useState } from "react";
import {
  FaUser,
  FaEnvelope,
  FaCommentDots,
  FaLightbulb,
  FaCheckCircle,
  FaHeadset,
  FaHorse,
} from "react-icons/fa";
import { UPLOAD_URL } from "../../services/api";
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
    <main className="suggestionPage">

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

          <p>
            Your ideas help us improve.
            <br />
            Share your suggestion or feedback and
            <br />
            our team will review it carefully.
          </p>

          <div className="suggestionInfoList">

            <div className="suggestionInfoItem">
              <span className="suggestionInfoIcon">
                <FaLightbulb />
              </span>

              <div>
                <strong>Share Your Idea</strong>
                <p>Tell us what you would like us to improve.</p>
              </div>
            </div>

            <div className="suggestionInfoItem">
              <span className="suggestionInfoIcon">
                <FaCheckCircle />
              </span>

              <div>
                <strong>Every Suggestion Matters</strong>
                <p>Every genuine submission is reviewed by our team.</p>
              </div>
            </div>

            <div className="suggestionInfoItem">
              <span className="suggestionInfoIcon">
                <FaHeadset />
              </span>

              <div>
                <strong>Need Assistance?</strong>
                <p>Our support team is available to help you.</p>
              </div>
            </div>

          </div>

        </div>

        {/* RIGHT SIDE FORM CARD */}
        <div className="suggestionFormCard">

          <form className="suggestionForm" onSubmit={handleSubmit}>

            {/* NAME */}
            <div className="suggestionField">
              <label htmlFor="suggestion-name">
                <FaUser className="suggestionFieldLabelIcon" />
                Name
              </label>

              <input
                id="suggestion-name"
                type="text"
                name="name"
                placeholder="Enter Name"
                value={formData.name}
                onChange={handleChange}
                required
              />
            </div>

            {/* EMAIL */}
            <div className="suggestionField">
              <label htmlFor="suggestion-email">
                <FaEnvelope className="suggestionFieldLabelIcon" />
                Email
              </label>

              <input
                id="suggestion-email"
                type="email"
                name="email"
                placeholder="Enter Email"
                value={formData.email}
                onChange={handleChange}
                required
              />
            </div>

            {/* SUGGESTION */}
            <div className="suggestionField">
              <label htmlFor="suggestion-message">
                <FaCommentDots className="suggestionFieldLabelIcon" />
                Suggestion
              </label>

              <textarea
                id="suggestion-message"
                name="message"
                placeholder="Type Your Suggestion"
                rows="4"
                value={formData.message}
                onChange={handleChange}
                required
              />
            </div>

            {/* CAPTCHA UI */}
            <div className="suggestionCaptcha">

              <div className="captchaLeft">

                <label className="captchaCheck">

                  <input
                    type="checkbox"
                    aria-label="I'm not a robot"
                    required
                  />

                  <span className="captchaCustomBox"></span>

                  <span className="captchaText">
                    I'm not a robot
                  </span>

                </label>

                <span className="captchaNotice">
                  reCAPTCHA is changing its terms of service.{" "}
                  <a href="#">Take action.</a>
                </span>

              </div>

              <div className="captchaBrand">

                <div className="captchaLogo">
                  ↻
                </div>

                <span>reCAPTCHA</span>

                <small>Privacy - Terms</small>

              </div>

            </div>

            {/* SUBMIT BUTTON */}
            <button type="submit" className="suggestionSubmitButton">
              <span>Submit</span>
            </button>

          </form>

        </div>

      </section>

    </main>
  );
}