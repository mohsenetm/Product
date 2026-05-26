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

    Note over S: 🏁 hotel_reserves #1001 — any existing status<br/>No state change will occur in this scenario

    CC->>S: Select template "No answer from guest" (id=1)
    Note over S: 💬 INSERT activity #4001<br/>type: comment · template_id: 1 · snapshot_id: NULL<br/>"Called 09121234567 at 14:32. No answer. Will retry in 30 min."

    Note over CC,S: ❌ hotel_reserves — NOT updated<br/>❌ hotel_reserve_snapshots — NO new row created<br/>❌ hotel_reserve_provider_transactions — NOT touched

    Note over CC,S: ⏳ 30 minutes later...

    CC->>S: Second call attempt
    Note over S: 💬 INSERT activity #4002<br/>type: comment · template_id: 1 · snapshot_id: NULL<br/>"Called again at 15:05. Still no answer."

    Note over CC,S: ⏳ 75 minutes later — different operator picks up

    CC->>S: Third call attempt (operator #9)
    Note over S: 💬 INSERT activity #4003<br/>type: comment · template_id: 1 · snapshot_id: NULL · users_id: 9<br/>"Third attempt at 16:20. Guest answered briefly then call dropped."

    Note over S: 📊 Final state: hotel_reserve_activities → 3 rows<br/>All other tables: unchanged
