%%{init: {
"theme": "base",
"themeVariables": {
"primaryColor": "#E1F5EE",
"primaryTextColor": "#085041",
"primaryBorderColor": "#0F6E56",
"secondaryColor": "#EEEDFE",
"secondaryTextColor": "#26215C",
"secondaryBorderColor": "#534AB7",
"tertiaryColor": "#FAEEDA",
"tertiaryTextColor": "#412402",
"tertiaryBorderColor": "#854F0B",
"noteBkgColor": "#FFF8EC",
"noteTextColor": "#633806",
"noteBorderColor": "#EF9F27",
"activationBkgColor": "#B5D4F4",
"activationBorderColor": "#185FA5",
"signalColor": "#0F6E56",
"signalTextColor": "#085041",
"labelBoxBkgColor": "#F1EFE8",
"labelBoxBorderColor": "#B4B2A9",
"labelTextColor": "#2C2C2A",
"loopTextColor": "#2C2C2A",
"sequenceNumberColor": "#ffffff",
"fontFamily": "Segoe UI, sans-serif",
"fontSize": "14px"
}
}}%%
sequenceDiagram
autonumber
actor G as 🧑 Guest
participant S as ⚙️ System
participant P2 as 🏨 Provider #2
participant CC as 📞 Call Center

    G->>+S: Submit booking (1 room · 2 nights)
    Note over S: 📝 INSERT hotel_reserves #1001 — status: created<br/>📝 INSERT hotel_reserve_rooms #201<br/>📝 INSERT hotel_reserve_room_availabilities ×2<br/>📸 INSERT snapshot #5001
    S-->>-G: ✅ Reservation created

    S->>+P2: request_booking
    Note over S: 🔄 UPDATE status → waiting_booking<br/>📸 INSERT snapshot #5002

    P2-->>-S: ❌ Rejected — ROOM_NOT_AVAILABLE

    Note over S: 🔄 UPDATE status → rejected_booking<br/>📸 INSERT snapshot #5003<br/>📋 INSERT provider_transaction #3001 (is_success: false)<br/>"No availability for room type DBL on 2026-06-10"

    CC->>S: Review rejection — log comment
    Note over S: 💬 INSERT activity #4001<br/>type: comment · template_id: 1 · snapshot_id: NULL<br/>"Provider returned ROOM_NOT_AVAILABLE. Guest will be contacted."
