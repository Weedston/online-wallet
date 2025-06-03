package com.example.anonbtcwallet

data class Transaction(
    val txid: String,
    val category: String,
    val amount: Double,
    val time: Long,
    val confirmations: Int,
    val address: String
)
