"use client";

import { useEffect, useState } from "react";
import { FaHorseHead, FaArrowDown } from "react-icons/fa";
import { NEW_URL, NEWS_URL } from "../../../services/api";
import { getApplicationForms } from "../../../services/applicationFormsService";
import "./ApplicationForm.css";

/*
 * SAFETY NET - old hard-coded list.
 * Only used when the API is unreachable or no form has been uploaded from the
 * admin panel yet, so the page is never blank during the roll-out.
 * Once every form is uploaded in turf-console > Application Forms Manager
 * you can delete this array, the two imports above (NEW_URL, NEWS_URL) and
 * the fallback branch inside load().
 */
const LEGACY_FORMS = [
    { name: "Application For License - Jockeys", file: "NewAPPLICATIONFORJOCKEY.pdf" },
    { name: "Application For License - Trainers", file: "NEWTRAINERSLICENSEAPPLICATION.pdf" },
    { name: "Trainee Apprentice School Programme", file: "Apprentice Jockeys Forms.pdf" },
    { name: "Application for License - Apprentice Trainers", file: "ASSTTRAINERSLICENSEAPPLICATION.pdf" },
    { name: "Authorised Agent", file: "Authorised Agent.pdf" },
    { name: "Authority to Trainer", file: "AUTHORITY TO TRAINER.pdf" },
    { name: "Badge Requisition Form", file: "Badge Requisition.pdf" },
    { name: "Classification", file: "Classification.pdf" },
    { name: "Club Membership", file: "club.pdf" },
    { name: "Declaration Form", file: "Declaration Form.pdf" },
    { name: "Entry Form", file: "Entry Form.pdf" },
    { name: "Equipment Form", file: "Equipment Form.pdf" },
    { name: "Foreign Jockeys Declaration", file: "Foreign Jockeys Declaration.pdf" },
    { name: "Foreign Jockey Application", file: "Foreign Jockey App.DOC" },
    { name: "ID-Card Form", file: "Id Card Form.pdf" },
    { name: "Jockey Retainer", file: "Jockey Retainer.pdf" },
    { name: "Joint Ownership", file: "Joint Ownership.pdf" },
    { name: "Lease Form (Older Horses)", file: "Lease Form (Older Horses).pdf" },
    { name: "Lease Form (2 Yr Old Horses Only)", file: "Lease Form (2 Yr Old Horses Only).pdf" },
    { name: "Left Charge - Leaving Centre", file: "Left Charge - Leaving Centre.pdf" },
    { name: "Proposal for Life Membership - Members Children", file: "LifeMembershipMembersChildren.pdf", base: NEWS_URL },
    { name: "Limited Company", file: "LIMITED COMPANY.pdf" },
    { name: "Owners Application", file: "NEW OWNERS APPLICATION.pdf" },
    { name: "Ownership Application For Dependents - Undertaking", file: "OWNERSHIP APPLICATION FOR DEPENDENTS - UNDERTAKING.pdf" },
    { name: "Ownership for Director of a Limited Company", file: "OWNERSAPPLICATIONASDIRECTOR.pdf" },
    { name: "Ownership by Limited Liability Partnership (LLP)", file: "Limited Liability Partnership Application Form 2011.pdf" },
    { name: "Ownership by Partnership Firm", file: "PARTNERSHIP FIRM.pdf" },
    { name: "Racing Syndicate", file: "RACING SYN.doc" },
    { name: "Registration Of Name", file: "REGISTRATION OF NAME.pdf" },
    { name: "Registration Of Racing Colours", file: "Racing Colour.pdf" },
    { name: "Sale With Contingency", file: "SALE WITH CONTINGENCY.doc" },
    { name: "Sale Transfer", file: "Sale Form.pdf" },
    { name: "Scratching - White Form", file: "Scratching Form-White.pdf" },
    { name: "Scratching - Pink Form", file: "Scratching Form-Pink.pdf" },
    { name: "Scratching - Yellow Form", file: "Scratching Form-Yellow.pdf" },
    { name: "Take Charge Form", file: "Take Charge Form.pdf" },
    { name: "Notification of Birth", file: "Notification of Birth.pdf" },
    { name: "Certificate of Identity", file: "Certificate Of Identity.pdf" },
    { name: "Pink Form", file: "Pink Form.pdf" },
    { name: "Sale Form - SBAI", file: "Sale Transfer Form-SBAI.pdf" },
];

// inline style for the loading / empty message (no CSS file change needed)
const messageStyle = {
    margin: 0,
    padding: "28px 4px",
    textAlign: "center",
    color: "#6b7280",
    fontSize: "16px",
};

const legacyToForm = (form, index) => ({
    id: `legacy-${index}`,
    title: form.name,
    fileType: (form.file.split(".").pop() || "").toLowerCase(),
    downloadUrl: form.base
        ? `${form.base}/${form.file}`
        : `${NEW_URL}/aplication_forms/${form.file}`,
});

export default function ApplicationForm() {
    const [forms, setForms] = useState([]);
    const [status, setStatus] = useState("loading"); // loading | ready | error

    useEffect(() => {
        let active = true;

        async function load() {
            try {
                const list = await getApplicationForms();

                if (!active) return;

                if (list.length > 0) {
                    setForms(list);
                } else {
                    // nothing uploaded yet -> keep showing the old list
                    setForms(LEGACY_FORMS.map(legacyToForm));
                }
                setStatus("ready");
            } catch (error) {
                console.error("Application forms error :", error);

                if (!active) return;

                setForms(LEGACY_FORMS.map(legacyToForm));
                setStatus("ready");
            }
        }

        load();

        return () => {
            active = false;
        };
    }, []);

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Application Forms</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    {status === "loading" ? (
                        <p style={messageStyle} role="status">Loading application forms...</p>
                    ) : forms.length === 0 ? (
                        <p style={messageStyle} role="status">No application forms are available right now.</p>
                    ) : (
                        <div className="formsList">
                            {forms.map((form) => (
                                <div className="formRow" key={form.id}>
                                    <span className="formName">{form.title}</span>

                                    <a
                                        href={form.downloadUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="formDownloadBtn"
                                        aria-label={`Download ${form.title}`}
                                        title={`Download ${form.title}`}
                                    >
                                        <FaArrowDown />
                                    </a>
                                </div>
                            ))}
                        </div>
                    )}

                </div>

            </div>

        </section>

    );

}