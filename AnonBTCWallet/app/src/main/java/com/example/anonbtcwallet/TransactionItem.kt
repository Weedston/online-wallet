data class TransactionItem(
    val type: String, // "Входящая" или "Исходящая"
    val address: String,
    val amount: String,
    val date: String
)
