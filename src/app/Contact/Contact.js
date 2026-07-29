"use client";

import "./Contact.css";
import { UPLOAD_URL } from "../../services/api";

import {
    FaUser,
    FaEnvelope,
    FaCommentDots,
    FaHorse,
    FaPhoneAlt,
    FaMapMarkerAlt,
} from "react-icons/fa";

export default function Contact() {

    const handleSubmit = (event) => {
        event.preventDefault();

        const formData = new FormData(event.currentTarget);

        const contactData = {
            name: formData.get("name"),
            email: formData.get("email"),
            message: formData.get("message"),
        };

        console.log("Contact Form Data:", contactData);
    };

    return (
        <main className="contactPage">

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

                        {/* CAPTCHA UI */}
                        <div className="contactCaptcha">

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
                        <button
                            type="submit"
                            className="contactSubmitButton"
                        >
                            <span>Submit</span>
                        </button>

                    </form>

                </div>

            </section>

        </main>
    );
}