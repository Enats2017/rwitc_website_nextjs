"use client";

import { useState } from "react";
import { FaUser, FaEnvelope, FaCommentDots, FaPhoneAlt, FaMapMarkerAlt } from "react-icons/fa";
import "./Contact.css";

export default function Contact() {

    const [formData, setFormData] = useState({
        name: "",
        email: "",
        message: "",
    });

    function handleChange(e) {
        setFormData({
            ...formData,
            [e.target.name]: e.target.value,
        });
    }

    function handleSubmit(e) {
        e.preventDefault();

        // TODO: connect to backend API once available
        console.log("Contact form submitted:", formData);
    }

    return (
        <section className="contactPage">

            <div className="contactWrapper">

                {/* LEFT: IMAGE */}
                <div className="contactImageSide">

                    <div className="contactImageShape">
                        <img
                            src="/image/horse_race2.jpg"
                            alt="Royal Western India Turf Club racecourse"
                        />

                
                    </div>

                    <div className="contactCurveLight" />

                </div>

                {/* MIDDLE: INFO */}
                <div className="contactInfoSide">

                    <h1>Get In Touch</h1>
                    <span className="contactDivider" />

                    <p className="contactIntro">
                        Have a question or need assistance? Send us a message
                        and we&apos;ll respond as soon as possible.
                    </p>

                    <div className="contactDetailsList">

                        <div className="contactDetailItem">
                            <span className="contactDetailIcon">
                                <FaPhoneAlt />
                            </span>
                            <div className="contactDetailText">
                                <h4>Call Us</h4>
                                <p>+91 12345 67890</p>
                            </div>
                        </div>

                        <div className="contactDetailItem">
                            <span className="contactDetailIcon">
                                <FaEnvelope />
                            </span>
                            <div className="contactDetailText">
                                <h4>Email Us</h4>
                                <p>info@rwitc.com</p>
                            </div>
                        </div>

                        <div className="contactDetailItem">
                            <span className="contactDetailIcon">
                                <FaMapMarkerAlt />
                            </span>
                            <div className="contactDetailText">
                                <h4>Visit Us</h4>
                                <p>Royal Western India Turf Club, Mumbai</p>
                            </div>
                        </div>

                    </div>

                </div>

                {/* RIGHT: FORM */}
                <form className="contactFormCard" onSubmit={handleSubmit}>

                    <div className="contactFormRow">

                        <div className="contactField">
                            <label className="contactFieldLabel" htmlFor="name">
                                <FaUser /> Name
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Enter Name"
                                value={formData.name}
                                onChange={handleChange}
                            />
                        </div>

                        <div className="contactField">
                            <label className="contactFieldLabel" htmlFor="email">
                                <FaEnvelope /> Email
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Enter Email"
                                value={formData.email}
                                onChange={handleChange}
                            />
                        </div>

                    </div>

                    <div className="contactField">
                        <label className="contactFieldLabel" htmlFor="message">
                            <FaCommentDots /> Message
                        </label>
                        <textarea
                            id="message"
                            name="message"
                            placeholder="Type Your Message"
                            value={formData.message}
                            onChange={handleChange}
                        />
                    </div>

                    {/* CAPTCHA PLACEHOLDER — replace with real reCAPTCHA once backend is ready */}
                    <div className="captchaBox">
                        <div className="captchaContent">
                            <div className="captchaLeft">
                                <span className="captchaCheckbox" />
                                <span>I&apos;m not a robot</span>
                            </div>

                            <p className="captchaNotice">
                                reCAPTCHA is changing its terms of service.
                                <br />
                                <a href="#">Take action.</a>
                            </p>
                        </div>

                        <div className="captchaBadge">
                            <img src="/image/recaptcha-logo.png" alt="reCAPTCHA" />
                            <span>reCAPTCHA</span>
                            <small>Privacy - Terms</small>
                        </div>
                    </div>

                    <button type="submit" className="contactSubmitBtn">
                        Submit
                    </button>

                </form>

            </div>

        </section>
    );
}