"use client";
import "./LiveStreamButton.css";
export default function LiveStreamButton() {
    return (
        <a href="#live-video" className="liveStreamButton">
            <span className="liveStreamPlay">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 4L19 12L6 20V4Z" fill="currentColor"/>
                </svg>
                <span className="liveStreamText">Live</span>
            </span>
            <span className="liveStreamDivider"></span>
            <span className="liveStreamStream">Stream</span>
        </a>
    );
}