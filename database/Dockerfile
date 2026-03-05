FROM python:3.11-slim

WORKDIR /app

# Install dependencies required by the script
RUN pip install aiomysql python-dotenv

# The script running infinite loop
COPY run_generator.sh /app/run_generator.sh
RUN chmod +x /app/run_generator.sh

CMD ["/app/run_generator.sh"]
