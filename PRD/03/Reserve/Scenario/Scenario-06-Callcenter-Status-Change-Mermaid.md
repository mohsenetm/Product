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
participant CC as 📞 Call Center Operator
participant S as ⚙️ System

    Note over S: 🏁 hotel_reserves #1001 — status: confirmed<br/>No provider API call in this scenario

    CC->>S: Select action "Cancel by call center" (template id=5)

    S->>S: Step 1 — Update reservation
    Note over S: 🔄 UPDATE hotel_reserves #1001<br/>SET status = cancelled

    S->>S: Step 2 — Create snapshot
    Note over S: 📸 INSERT snapshot #5006<br/>SELECT current state FROM hotel_reserves #1001

    S->>S: Step 3 — Log action activity
    Note over S: 🎬 INSERT activity #4001<br/>type: action · template_id: 5 · users_id: 8<br/>snapshot_id: 5006 ← linked to new snapshot<br/>"Guest confirmed cancellation verbally.<br/>Refund of 12,000,000 Rials processed. Ref #PAY-9912."

    S-->>CC: ✅ Reservation cancelled — activity logged

    Note over CC,S: 🔀 Alternative: Mark reservation as worthless

    CC->>S: Mark reservation as worthless
    Note over S: 🔄 UPDATE hotel_reserves #1001<br/>SET is_worthless = true<br/>📸 INSERT snapshot #5007<br/>🎬 INSERT activity #4002<br/>type: action · template_id: NULL · snapshot_id: 5007<br/>"Created during load testing. Excluded from reports."
