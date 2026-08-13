"use client";

import {
    FaUserCircle,
    FaMapMarkerAlt,
    FaPhoneAlt,
    FaBuilding,
    FaUsers,
    FaChartLine,
    FaClipboardList,
} from "react-icons/fa";
import "./OfficialContact.css";

const OFFICIALS = [
    {
        name: "Mr. Niranjan Singh",
        designation: "Secretary",
        mumbaiTel: "022 - 62670100 / 20842551",
        puneTel: "020 - 66801800",
        email: "secretary@rwitc.com",
    },
    {
        name: "Mr. Satish R. Iyer",
        designation: "Additional Secretary & Keeper & Registrar, Indian Stud Book",
        mumbaiTel: "xxx",
        puneTel: "020 - 66801818",
        email: "addlsecretary@rwitc.com registrar@indianstudbook.com",
    },
    {
        name: "Mr. Shujaat Hussain",
        designation: "Chief Stipendiary Steward",
        mumbaiTel: "022 - 62670100",
        puneTel: "020 - 66801890",
        email: "stipes@rwitc.com",
    },
    {
        name: "Mr. Khan Itath Aman",
        designation: "Sr. Stipendiary Steward & Clerk of the Course",
        mumbaiTel: "022 - 62670100",
        puneTel: "020 - 66801890",
        email: "stipes@rwitc.com",
    },
    {
        name: "Dr. Pandurang G. Sawandkar",
        designation: "Chief Veterinary Officer, Equine Hospital",
        mumbaiTel: "022 - 62670100",
        puneTel: "020 - 66801890",
        email: "equinehospital@rwitc.com",
    },
    {
        name: "Dr. Yashpal Paithanpagare",
        designation: "Sr. Veterinary Officer, Regulatory",
        mumbaiTel: "022 - 62670100",
        puneTel: "020 - 66801890",
        email: "srvo@rwitc.com",
    },
    {
        name: "Mr. Shobhagsingh Bhati",
        designation: "Handicapper & Sr. Starter",
        mumbaiTel: "022 - 62670100",
        puneTel: "020 - 66801890",
        email: "handicapper@rwitc.com, starter@rwitc.com",
    },
    {
        name: "Mr. Aryaman Singh",
        designation: "Asst. Stipendiary Steward & Asst. Starter",
        mumbaiTel: "022 - 62670100",
        puneTel: "020 - 66801890",
        email: "stipes@rwitc.com",
    },
    {
        name: "Mr. Jerome Pereira",
        designation: "Starter",
        mumbaiTel: "022 - 62670100",
        puneTel: "020 - 66801890",
        email: "starter@rwitc.com",
    },
    {
        name: "Mr. Parag Kesarkar",
        designation: "General Manager (Totes) & IT",
        mumbaiTel: "022 - 62670171",
        puneTel: "xxx",
        email: "gmtotes@rwitc.com",
    },
    {
        name: "Mr. G. Venkatesan",
        designation: "General Manager, Marketing",
        mumbaiTel: "022 - 62670100",
        puneTel: "xxx",
        email: "gm.marketing@rwitc.com",
    },
    {
        name: "Mr. M.M.Somanathan Nair",
        designation: "Administrative Officer",
        mumbaiTel: "022 - 62670100",
        puneTel: "xxx",
        email: "ao@rwitc.com",
    },
    {
        name: "Mr. Gangadhar Kadam",
        designation: "Asst. Controller of Accounts",
        mumbaiTel: "022 - 62670100",
        puneTel: "xxx",
        email: "acoa@rwitc.com",
    },
];

const DEPARTMENT_EXTENSIONS_LEFT = [
    { icon: <FaUsers />, dept: "Accounts Dept.", ext: "122 & 127" },
    { icon: <FaBuilding />, dept: "Estate Office", ext: "113" },
    { icon: <FaUsers />, dept: "Membership", ext: "119" },
    { icon: <FaClipboardList />, dept: "Lawns Booking", ext: "208/110" },
];

const DEPARTMENT_EXTENSIONS_RIGHT = [
    { icon: <FaClipboardList />, dept: "Totalisators", ext: "123/350" },
    { icon: <FaUsers />, dept: "PA to Secretary", ext: "110" },
    { icon: <FaChartLine />, dept: "Racing Dept.", ext: "118" },
    { icon: <FaClipboardList />, dept: "PLD", ext: "114" },
];

export default function OfficialContact() {
    return (
        <section className="officialContactPage">

            <div className="officialContactHeader">
                <h1>Contact Us</h1>
                <p>Telephone Numbers &amp; E-Mail Addresses of Officials</p>
            </div>

            <div className="officialContactContainer">

                <div className="officialContactTableWrap">
                    <table className="officialContactTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Mumbai Tel.</th>
                                <th>Pune Tel.</th>
                                <th>E-Mail Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            {OFFICIALS.map((person, index) => (
                                <tr key={index}>
                                    <td className="officialNameCell" data-label="Name">
                                        <FaUserCircle className="officialNameIcon" />
                                        {person.name}
                                    </td>
                                    <td data-label="Designation">{person.designation}</td>
                                    <td data-label="Mumbai Tel.">{person.mumbaiTel}</td>
                                    <td data-label="Pune Tel.">{person.puneTel}</td>
                                    <td data-label="E-Mail Address">{person.email}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="officialInfoCards">

                    <div className="officialInfoCard">
                        <div className="officialInfoCardIcon">
                            <FaMapMarkerAlt />
                        </div>
                        <h3>Address</h3>
                        <p>
                            Address Race Course, K.K.Marg, Mahalakshmi, Mumbai City,
                            Maharashtra, 400034
                        </p>
                    </div>

                    <div className="officialInfoCard">
                        <div className="officialInfoCardIcon">
                            <FaPhoneAlt />
                        </div>
                        <h3>Board Numbers</h3>
                        <p>
                            Mumbai : 022 - 62670100<br />
                            Pune : 020 - 66801800; 66801890;
                        </p>
                        <p>
                            Club House, Mumbai (Hooves) :<br />
                            022-62670100 (Extn : 129) / 022 20842570
                        </p>
                        <p>
                            Turf Club House, Pune :<br />
                            020 - 26303300 ; 26362666
                        </p>
                    </div>

                    <div className="officialInfoCard">
                        <div className="officialInfoCardIcon">
                            <FaBuilding />
                        </div>
                        <h3>Department Extensions</h3>
                        <ul className="officialInfoExtList">
                            {DEPARTMENT_EXTENSIONS_LEFT.map((item, index) => (
                                <li key={index}>
                                    <span className="officialInfoExtLeft">
                                        {item.icon}
                                        {item.dept}
                                    </span>
                                    <span>{item.ext}</span>
                                </li>
                            ))}
                        </ul>
                        <a href="#department-extensions" className="officialViewAllBtn">
                            View All Extensions <span>&rsaquo;</span>
                        </a>
                    </div>

                </div>

                <div id="department-extensions" className="officialDeptExtSection">
                    <h2>
                        <span className="officialDeptExtDeco">&#10087;</span>
                        Department Extensions
                        <span className="officialDeptExtDeco">&#10087;</span>
                    </h2>

                    <div className="officialDeptExtGrid">
                        <div className="officialDeptExtTableWrap">
                            <table className="officialDeptExtTable">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Extension</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {DEPARTMENT_EXTENSIONS_LEFT.map((item, index) => (
                                        <tr key={index}>
                                            <td className="officialDeptCell">
                                                {item.icon}
                                                {item.dept}
                                            </td>
                                            <td>{item.ext}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="officialDeptExtTableWrap">
                            <table className="officialDeptExtTable">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Extension</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {DEPARTMENT_EXTENSIONS_RIGHT.map((item, index) => (
                                        <tr key={index}>
                                            <td className="officialDeptCell">
                                                {item.icon}
                                                {item.dept}
                                            </td>
                                            <td>{item.ext}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </section>
    );
}