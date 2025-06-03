import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class TransactionAdapter(private val items: List<TransactionItem>) :
    RecyclerView.Adapter<TransactionAdapter.ViewHolder>() {

    class ViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val tvType: TextView = view.findViewById(R.id.tvType)
        val tvAddress: TextView = view.findViewById(R.id.tvAddress)
        val tvAmount: TextView = view.findViewById(R.id.tvAmount)
        val tvDate: TextView = view.findViewById(R.id.tvDate)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_transaction, parent, false)
        return ViewHolder(view)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val tx = items[position]
        holder.tvType.text = tx.type
        holder.tvAddress.text = "Адрес: ${tx.address}"
        holder.tvAmount.text = "${tx.amount} BTC"
        holder.tvDate.text = tx.date

        holder.tvAmount.setTextColor(
            if (tx.type == "Входящая") 0xFF00FF00.toInt() else 0xFFFF4444.toInt()
        )
    }

    override fun getItemCount(): Int = items.size
}
