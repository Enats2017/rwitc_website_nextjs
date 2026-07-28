"use client";
import "../styles/watermark.css";
export default function Watermark() {
    const items = Array.from({ length: 24 });
    return (
        <div className="websiteWatermark">
            {
                items.map((_, index) => (
                    <span key={index}> RWITC.COM </span>
                ))
            }
        </div>
    );
}