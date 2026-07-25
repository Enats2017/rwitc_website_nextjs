"use client";
import { UPLOAD_URL } from "../../../services/api";
import "./TopHeader.css";
import { FaBars, FaCamera, } from "react-icons/fa";
export default function TopHeader() {
    return (
        <header className="header">
            <div className="header__wrapper">
                {/* LEFT SIDE */}
                <div className="header__brand">
                    <button className="menuButton"> <FaBars /> </button>
                    <div className="logo">
                        <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC Logo" draggable="false"/>
                    </div>
                    <div className="clubInfo">
                        <h2> Royal Western India Turf Club Ltd. </h2>
                        <p> Since 1925 • Pune & Mumbai Race Courses </p>
                    </div>
                </div>
                {/* RIGHT SIDE */}
                <div className="header__actions">
                    <button className="glassButton"> Suggestions </button>
                    <button className="glassButton"> Contact </button>
                    <button className="glassButton"> Top Stories </button>
                    <button className="youtubeButton">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="42" height="42">
                            <path fill="#FF0000" d="M549.655 124.083c-6.281-23.65-24.814-42.183-48.464-48.464C458.797 64 288 64 288 64S117.203 64 74.809 75.619c-23.65 6.281-42.183 24.814-48.464 48.464C16 166.477 16 256 16 256s0 89.523 10.345 131.917c6.281 23.65 24.814 42.183 48.464 48.464C117.203 448 288 448 288 448s170.797 0 213.191-11.619c23.65-6.281 42.183-24.814 48.464-48.464C560 345.523 560 256 560 256s0-89.523-10.345-131.917z" />
                            <path fill="#ffffff" d="M232 334.5V177.5L361 256 232 334.5z" />
                        </svg>
                    </button>
                    <button className="galleryButton"> <FaCamera /> </button>
                </div>
            </div>
        </header>
    );
}