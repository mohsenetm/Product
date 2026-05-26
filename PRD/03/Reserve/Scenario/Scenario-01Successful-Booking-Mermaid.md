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
participant P as 🏨 Provider (GRS)
participant Pay as 💳 Payment

    G->>+S: Submit booking (2 rooms · 3 nights)
    Note over S: 📝 INSERT hotel_reserves — status: created<br/>📝 INSERT hotel_reserve_rooms ×2<br/>📝 INSERT hotel_reserve_room_availabilities ×6<br/>📸 INSERT snapshot #5001
    S-->>-G: ✅ Reservation created

    S->>+P: request_booking
    Note over S: 🔄 UPDATE status → request_booking<br/>📸 INSERT snapshot #5002
    P-->>-S: Booked ✓ (GRS-78821)
    Note over S: 🔄 UPDATE status → booking<br/>🔑 provider_confirmation_code = GRS-78821<br/>⏱️ update expired_at<br/>📸 INSERT snapshot #5003<br/>📋 INSERT provider_transaction #1

    S->>+Pay: Capture payment
    Pay-->>-S: Payment OK ✓
    Note over S: 🔄 UPDATE status → paid<br/>📸 INSERT snapshot #5004

    S->>+P: request_confirmation
    P-->>-S: confirmed ✓
    Note over S: 🔄 UPDATE status → confirmed<br/>📸 INSERT snapshot #5005<br/>📋 INSERT provider_transaction #2

    S-->>G: 🎉 Booking confirmed!
