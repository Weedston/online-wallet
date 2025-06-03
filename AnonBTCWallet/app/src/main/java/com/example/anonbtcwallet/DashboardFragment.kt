package com.example.anonbtcwallet

import android.annotation.SuppressLint
import android.content.Context
import android.content.ClipData
import android.content.ClipboardManager
import android.content.res.Configuration
import android.graphics.Bitmap
import android.graphics.Color
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.google.zxing.BarcodeFormat
import com.google.zxing.WriterException
import com.google.zxing.qrcode.QRCodeWriter
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.io.IOException
import java.text.SimpleDateFormat
import java.util.*

class DashboardFragment : Fragment() {

    private lateinit var balanceTextView: TextView
    private lateinit var transactionsTextView: TextView
    private lateinit var addressTextView: TextView
    private lateinit var qrcodeImageView: ImageView
    private lateinit var transactionsRecyclerView: RecyclerView

    private var updateHandler: Handler? = null
    private var updateRunnable: Runnable? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setHasOptionsMenu(true)
    }

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View? {
        val prefs = requireActivity().getSharedPreferences("auth", Context.MODE_PRIVATE)
        val lang = prefs.getString("lang", "ru") ?: "ru"
        setAppLocale(lang)

        val view = inflater.inflate(R.layout.fragment_dashboard, container, false)
        balanceTextView = view.findViewById(R.id.balanceTextView)
        transactionsTextView = view.findViewById(R.id.transactionsTitle)
        addressTextView = view.findViewById(R.id.addressTextView)
        qrcodeImageView = view.findViewById(R.id.qrcodeImageView)
        transactionsRecyclerView = view.findViewById(R.id.transactionsRecyclerView)

        transactionsRecyclerView.layoutManager = LinearLayoutManager(requireContext())

        // Сделать весь текст светлым
        val lightColor = Color.WHITE
        balanceTextView.setTextColor(lightColor)
        transactionsTextView.setTextColor(lightColor)
        addressTextView.setTextColor(lightColor)

        fetchDashboardData()
        startAutoUpdate()

        return view
    }

    override fun onDestroyView() {
        super.onDestroyView()
        stopAutoUpdate()
    }

    override fun onCreateOptionsMenu(menu: android.view.Menu, inflater: android.view.MenuInflater) {
        inflater.inflate(R.menu.main_menu, menu)
        super.onCreateOptionsMenu(menu, inflater)
    }

    override fun onOptionsItemSelected(item: android.view.MenuItem): Boolean {
        when (item.itemId) {
            R.id.action_dashboard -> {
                requireActivity().supportFragmentManager.beginTransaction()
                    .replace(R.id.contentFrame, DashboardFragment())
                    .commit()

                return true
            }
            R.id.action_transfer -> {
                return true
            }
            R.id.action_support -> {
                return true
            }
            R.id.action_profile -> {
                return true
            }
            R.id.action_reset_account -> {
                val prefs = requireActivity().getSharedPreferences("auth", Context.MODE_PRIVATE)
                prefs.edit().remove("sid").remove("pin").remove("token").remove("user_id").remove("wallet").apply()
                val intent = android.content.Intent(requireContext(), SplashActivity::class.java)
                intent.addFlags(android.content.Intent.FLAG_ACTIVITY_NEW_TASK or android.content.Intent.FLAG_ACTIVITY_CLEAR_TASK)
                startActivity(intent)
                requireActivity().finish()
                return true
            }
        }
        return super.onOptionsItemSelected(item)
    }

    private fun setAppLocale(language: String) {
        val locale = Locale(language)
        Locale.setDefault(locale)
        val config = Configuration()
        config.setLocale(locale)
        requireContext().resources.updateConfiguration(config, requireContext().resources.displayMetrics)
    }

    private fun fetchDashboardData() {
        val prefs = requireActivity().getSharedPreferences("auth", Context.MODE_PRIVATE)
        val userId = prefs.getInt("user_id", 0)
        val token = prefs.getString("token", null)

        if (userId == 0 || token.isNullOrEmpty()) {
            balanceTextView.text = getString(R.string.error_user_not_authenticated)
            addressTextView.text = ""
            transactionsTextView.text = ""
            transactionsRecyclerView.adapter = null
            qrcodeImageView.setImageBitmap(null)
            return
        }

        val client = OkHttpClient()
        val json = JSONObject()
        json.put("user_id", userId)
        json.put("token", token)
        val body = json.toString().toRequestBody("application/json; charset=utf-8".toMediaType())
        val request = Request.Builder()
            .url("http://46-30-41-84.sslip.io/api/dashboard.php")
            .post(body)
            .build()

        client.newCall(request).enqueue(object : Callback {
            override fun onFailure(call: Call, e: IOException) {
                requireActivity().runOnUiThread {
                    balanceTextView.text = getString(R.string.network_error)
                    addressTextView.text = ""
                    transactionsTextView.text = ""
                    transactionsRecyclerView.adapter = null
                    qrcodeImageView.setImageBitmap(null)
                }
            }

            @SuppressLint("SimpleDateFormat")
            override fun onResponse(call: Call, response: Response) {
                val responseData = response.body?.string()
                requireActivity().runOnUiThread {
                    if (!response.isSuccessful || responseData == null) {
                        if (response.code == 401) {
                            restartApp()
                            return@runOnUiThread
                        }
                        balanceTextView.text = getString(R.string.error_unable_to_fetch_data)
                        addressTextView.text = ""
                        transactionsTextView.text = ""
                        transactionsRecyclerView.adapter = null
                        qrcodeImageView.setImageBitmap(null)
                        return@runOnUiThread
                    }

                    try {
                        val json = JSONObject(responseData)
                        // Лог для отладки
                        android.util.Log.d("DashboardFragment", "API response: $json")

                        if (json.has("error")) {
                            val error = json.optString("error")
                            if (error.contains("Unauthorized", true) || error.contains("Invalid token", true)) {
                                restartApp()
                                return@runOnUiThread
                            }
                            android.util.Log.w("DashboardFragment", "Warning: $error")
                        }

                        val balance = json.optString("balance", "0.00000000")
                        val btcAddress = json.optString("btc_address", "")
                        balanceTextView.text = getString(R.string.balance_label, balance)
                        addressTextView.text = getString(R.string.address_label, btcAddress)

                        // QR-код
                        qrcodeImageView.setImageBitmap(
                            if (btcAddress.isNotBlank()) generateQRCode(btcAddress) else null
                        )

                        // Парсим транзакции
                        val txList = mutableListOf<TransactionItem>()
                        if (json.has("transactions")) {
                            val txArray = json.getJSONArray("transactions")
                            for (i in 0 until txArray.length()) {
                                val tx = txArray.getJSONObject(i)
                                val category = tx.optString("category", "")
                                val amount = tx.optDouble("amount", 0.0)
                                val confirmations = tx.optInt("confirmations", 0)
                                val time = tx.optLong("time", 0)
                                val txid = tx.optString("txid", "")
                                val address = tx.optString("address", "")

                                val type = if (category == "receive") "Входящая" else "Исходящая"
                                val date = SimpleDateFormat("dd.MM.yyyy HH:mm", Locale.getDefault())
                                    .format(Date(time * 1000))

                                txList.add(TransactionItem(type, address, "$amount BTC", date, txid, confirmations))
                            }
                        }

                        transactionsRecyclerView.adapter = TransactionAdapter(txList)

                        transactionsTextView.text =
                            if (txList.isEmpty()) getString(R.string.no_transactions) else getString(R.string.transactions_label)

                    } catch (e: Exception) {
                        balanceTextView.text = getString(R.string.error_invalid_data_format)
                        addressTextView.text = ""
                        transactionsTextView.text = ""
                        transactionsRecyclerView.adapter = null
                        qrcodeImageView.setImageBitmap(null)
                        android.util.Log.e("DashboardFragment", "Parse error", e)
                    }
                }
            }
        })
    }

    private fun generateQRCode(text: String): Bitmap? {
        return try {
            val size = 512
            val bits = QRCodeWriter().encode(text, BarcodeFormat.QR_CODE, size, size)
            val bmp = Bitmap.createBitmap(size, size, Bitmap.Config.RGB_565)
            for (x in 0 until size) {
                for (y in 0 until size) {
                    bmp.setPixel(x, y, if (bits[x, y]) Color.BLACK else Color.WHITE)
                }
            }
            bmp
        } catch (e: WriterException) {
            null
        }
    }

    private fun startAutoUpdate() {
        updateHandler = Handler(Looper.getMainLooper())
        updateRunnable = object : Runnable {
            override fun run() {
                fetchDashboardData()
                updateHandler?.postDelayed(this, 10_000)
            }
        }
        updateHandler?.postDelayed(updateRunnable!!, 10_000)
    }

    private fun stopAutoUpdate() {
        updateHandler?.removeCallbacksAndMessages(null)
        updateHandler = null
        updateRunnable = null
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        addressTextView.setOnClickListener {
            val clipboard = requireContext().getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
            val clip = ClipData.newPlainText("BTC Address", addressTextView.text.toString())
            clipboard.setPrimaryClip(clip)
            Toast.makeText(requireContext(), "Адрес скопирован", Toast.LENGTH_SHORT).show()
        }
    }
}

// Класс для хранения данных транзакции
data class TransactionItem(
    val type: String,
    val address: String,
    val amount: String,
    val date: String,
    val txid: String = "",
    val confirmations: Int = 0
)

// Адаптер для RecyclerView
class TransactionAdapter(private val items: List<TransactionItem>) :
    RecyclerView.Adapter<TransactionAdapter.TransactionViewHolder>() {

    class TransactionViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val typeTextView: TextView = view.findViewById(R.id.txType)
        val addressTextView: TextView = view.findViewById(R.id.txAddress)
        val amountTextView: TextView = view.findViewById(R.id.txAmount)
        val dateTextView: TextView = view.findViewById(R.id.txDate)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): TransactionViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.item_transaction, parent, false)
        return TransactionViewHolder(view)
    }

    override fun onBindViewHolder(holder: TransactionViewHolder, position: Int) {
        val item = items[position]
        holder.typeTextView.text = item.type
        holder.addressTextView.text = item.address
        holder.amountTextView.text = item.amount
        holder.dateTextView.text = item.date

        // Подсветка белым цветом
        holder.typeTextView.setTextColor(Color.WHITE)
        holder.addressTextView.setTextColor(Color.WHITE)
        holder.amountTextView.setTextColor(Color.WHITE)
        holder.dateTextView.setTextColor(Color.WHITE)
    }

    override fun getItemCount(): Int = items.size
}

    private fun restartApp() {
        val prefs = requireActivity().getSharedPreferences("auth", Context.MODE_PRIVATE)
        prefs.edit().clear().apply()

        val intent = android.content.Intent(requireContext(), SplashActivity::class.java)
        intent.addFlags(android.content.Intent.FLAG_ACTIVITY_NEW_TASK or android.content.Intent.FLAG_ACTIVITY_CLEAR_TASK)
        startActivity(intent)
        requireActivity().finish()
    }

    private fun generateQRCode(text: String): Bitmap? {
        return try {
            val size = 512
            val bits = QRCodeWriter().encode(text, BarcodeFormat.QR_CODE, size, size)
            val bmp = Bitmap.createBitmap(size, size, Bitmap.Config.RGB_565)
            for (x in 0 until size) {
                for (y in 0 until size) {
                    bmp.setPixel(x, y, if (bits[x, y]) Color.BLACK else Color.WHITE)
                }
            }
            bmp
        } catch (e: WriterException) {
            null
        }
    }

    private fun startAutoUpdate() {
        updateHandler = Handler(Looper.getMainLooper())
        updateRunnable = object : Runnable {
            override fun run() {
                fetchDashboardData()
                updateHandler?.postDelayed(this, 10_000)
            }
        }
        updateHandler?.postDelayed(updateRunnable!!, 10_000)
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        addressTextView.setOnClickListener {
            val clipboard = requireContext().getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
            val clip = ClipData.newPlainText("BTC Address", addressTextView.text.toString())
            clipboard.setPrimaryClip(clip)
            Toast.makeText(requireContext(), "Адрес скопирован", Toast.LENGTH_SHORT).show()
        }

    }

    private fun stopAutoUpdate() {
        updateHandler?.removeCallbacksAndMessages(null)
        updateHandler = null
        updateRunnable = null
    }
}
