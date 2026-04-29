# Business Rules & Logic

## Payment Module

### Split Payment Modal

- **Persistence:** Selected payment methods (Cash, Bank Transfer, Card) in the Split Payment modal must persist even if the modal is closed and reopened. The list is cleared only when explicitly resetting or creating a new order.
- **Default Methods:** The modal supports dynamic payment methods passed from the parent view.

## UI/UX Rules

### Network Status

- **Offline Indication:** The system previously used an Alpine.js banner to warn of network loss. This has been removed due to false positives in the testing environment. Network status handling is currently implicit (browser default behavior).
