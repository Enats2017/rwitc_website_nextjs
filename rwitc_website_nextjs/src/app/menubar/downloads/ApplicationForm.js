"use client";

import { FaHorseHead, FaArrowDown } from "react-icons/fa";
import { NEW_URL, NEWS_URL } from "../../../services/api";
import "./ApplicationForm.css";

export default function ApplicationForm() {

    const forms = [
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
        { name: "Foreign Jockey Application", file: "Foreign-Jockey-Application.pdf" },
        { name: "ID-Card Form", file: "Id Card Form.pdf" },
        { name: "Jockey Retainer", file: "Jockey Retainer.pdf" },
        { name: "Joint Ownership", file: "Joint Ownership.pdf" },
        { name: "Lease Form (Older Horses)", file: "Lease Form (Older Horses).pdf" },
        { name: "Lease Form (2 Yr Old Horses Only)", file: "Lease Form (2 Yr Old Horses Only).pdf" },
        { name: "Left Charge - Leaving Centre", file: "Left Charge - Leaving Centre.pdf" },
        { name: "Proposal for Life Membership - Members Children", file: "LifeMembershipMembersChildren.pdf", base: NEWS_URL,},
        { name: "Limited Company", file: "LIMITED COMPANY.pdf" },
        { name: "Owners Application", file: "NEW OWNERS APPLICATION.pdf" },
        { name: "Ownership Application For Dependents - Undertaking", file: "OWNERSHIP APPLICATION FOR DEPENDENTS - UNDERTAKING.pdf" },
        { name: "Ownership for Director of a Limited Company", file: "OWNERSAPPLICATIONASDIRECTOR.pdf" },
        { name: "Ownership by Limited Liability Partnership (LLP)", file: "Limited Liability Partnership Application Form 2011.pdf" },
        { name: "Ownership by Partnership Firm", file: "PARTNERSHIP FIRM.pdf" },
        { name: "Racing Syndicate", file: "Racing-Syndicate.pdf" },
        { name: "Registration Of Name", file: "REGISTRATION OF NAME.pdf" },
        { name: "Registration Of Racing Colours", file: "Racing Colour.pdf" },
        { name: "Sale With Contingency", file: "Sale-With-Contingency.pdf" },
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

                    <div className="formsList">
                        {forms.map((form, index) => {

                            const base = form.base || NEW_URL;
                            const path = form.base
                                ? `${base}/${form.file}`
                                : `${base}/aplication_forms/${form.file}`;

                            return (
                                <div className="formRow" key={index}>
                                    <span className="formName">{form.name}</span>

                                    
                                    <a    href={path}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="formDownloadBtn"
                                        aria-label={`Download ${form.name}`}
                                        title={`Download ${form.name}`}
                                    >
                                        <FaArrowDown />
                                    </a>
                                </div>
                            );

                        })}
                    </div>

                </div>

            </div>

        </section>

    );

}